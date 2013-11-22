<?php
namespace Rbs\Catalog\Attribute;

use Rbs\Catalog\Documents\Attribute;

/**
 * @name \Rbs\Catalog\Attribute\AttributeManager
 */
class AttributeManager
{
	/**
	 * @var \Change\Collection\CollectionManager
	 */
	protected $collectionManager;

	/**
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;

	/**
	 * @var \Change\Db\DbProvider
	 */
	protected $dbProvider;

	/**
	 * @var \Change\I18n\I18nManager
	 */
	protected $i18nManager;

	/**
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @return $this
	 */
	public function setDocumentManager($documentManager)
	{
		$this->documentManager = $documentManager;
		return $this;
	}

	/**
	 * @return \Change\Documents\DocumentManager
	 */
	protected function getDocumentManager()
	{
		return $this->documentManager;
	}

	/**
	 * @param \Change\Collection\CollectionManager $collectionManager
	 * @return $this
	 */
	public function setCollectionManager($collectionManager)
	{
		$this->collectionManager = $collectionManager;
		return $this;
	}

	/**
	 * @return \Change\Collection\CollectionManager
	 */
	protected function getCollectionManager()
	{
		return $this->collectionManager;
	}

	/**
	 * @param \Change\Db\DbProvider $dbProvider
	 * @return $this
	 */
	public function setDbProvider($dbProvider)
	{
		$this->dbProvider = $dbProvider;
		return $this;
	}

	/**
	 * @return \Change\Db\DbProvider
	 */
	protected function getDbProvider()
	{
		return $this->dbProvider;
	}

	/**
	 * @param \Change\I18n\I18nManager $i18nManager
	 * @return $this
	 */
	public function setI18nManager($i18nManager)
	{
		$this->i18nManager = $i18nManager;
		return $this;
	}

	/**
	 * @return \Change\I18n\I18nManager
	 */
	protected function getI18nManager()
	{
		return $this->i18nManager;
	}


	/**
	 * Use DbProvider
	 * @param \Rbs\Catalog\Documents\Product|integer $product
	 * @return array
	 */
	public function getAttributeValues($product)
	{
		$productId = ($product instanceof \Rbs\Catalog\Documents\Product) ? $product->getId() : intval($product);
		$qb = $this->getDbProvider()->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->select($fb->column('attribute_id'), $fb->alias($fb->getDocumentColumn('valueType'), 'valueType'),
			$fb->column('integer_value'), $fb->column('date_value'), $fb->column('float_value'),
			$fb->column('string_value'), $fb->column('text_value'));
		$qb->from($fb->table('rbs_catalog_dat_attribute'));
		$qb->innerJoin($fb->getDocumentTable('Rbs_Catalog_Attribute'),
			$fb->eq($fb->getDocumentColumn('id'), $fb->column('product_id')));
		$qb->where($fb->eq($fb->column('product_id'), $fb->integerParameter('productId')));
		$query = $qb->query();
		$query->bindParameter('productId', $productId);
		$rows = $query->getResults($query->getRowsConverter()->addIntCol('attribute_id', 'integer_value')
			->addStrCol('valueType', 'string_value')->addNumCol('float_value')->addDtCol('date_value')->addTxtCol('text_value'));
		$values = array();
		
		foreach ($rows as $row)
		{
			$val = array('id' => $row['attribute_id'], 'valueType' => $row['valueType']);
			switch ($row['valueType'])
			{
				case Attribute::TYPE_BOOLEAN:
					$val['value'] = $row['integer_value'] != 0;
					break;
				case Attribute::TYPE_INTEGER:
				case Attribute::TYPE_DOCUMENTID:
					$val['value'] = $row['integer_value'];
					break;
				case Attribute::TYPE_DATETIME:
					/* @var $v \DateTime */
					$v = $row['date_value'];
					$val['value'] = $v->format(\DateTime::ISO8601);
					break;
				case Attribute::TYPE_FLOAT:
					$val['value'] = $row['float_value'];
					break;
				case Attribute::TYPE_CODE:
					$val['value'] = $row['string_value'];
					break;
				case Attribute::TYPE_TEXT:
					$val['value'] = $row['text_value'];
					break;
				default:
					$val['value'] = null;
					break;
			}
			$values[] = $val;
		}
		return $values;
	}

	/**
	 * Use DbProvider
	 * @param \Rbs\Catalog\Documents\Product|integer $product
	 * @param array $values
	 */
	public function setAttributeValues($product, $values)
	{
		$productId = ($product instanceof \Rbs\Catalog\Documents\Product) ? $product->getId() : intval($product);
		if (is_array($values))
		{
			$defined = $this->getDefinedAttributesValues($productId);
			foreach ($values as $value)
			{
				if ($value['valueType'] === Attribute::TYPE_PROPERTY)
				{
					continue;
				}

				if (isset($defined[$value['id']]))
				{
					$this->updateAttributeValue($defined[$value['id']], $value);
				}
				else
				{
					$defined[$value['id']] = $this->insertAttributeValue($productId, $value);
				}
			}
		}
		else
		{
			$this->deleteAttributeValue($productId);
		}
	}

	/**
	 * @param integer $productId
	 * @return array
	 */
	protected function getDefinedAttributesValues($productId)
	{
		$qb = $this->getDbProvider()->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->select($fb->column('id'), $fb->column('attribute_id'));
		$qb->from($fb->table('rbs_catalog_dat_attribute'));
		$qb->where($fb->eq($fb->column('product_id'), $fb->integerParameter('productId')));
		$query = $qb->query();
		$query->bindParameter('productId', $productId);
		$result = array();
		foreach ($query->getResults($query->getRowsConverter()->addIntCol('id', 'attribute_id')) as $row)
		{
			$result[$row['attribute_id']] = $row['id'];
		}
		return $result;
	}

	protected function dispatchValue($valueType, $value)
	{
		//integer_value, float_value, date_value, string_value, text_value
		$result = array(null, null, null, null, null);
		if ($value !== null)
		{
			switch ($valueType)
			{
				case Attribute::TYPE_BOOLEAN:
					$result[0] = $value ? 1 : 0;
					break;
				case Attribute::TYPE_INTEGER:
				case Attribute::TYPE_DOCUMENTID:
					$result[0] = is_array($value) ? intval($value['id']) : intval($value);
					break;
				case Attribute::TYPE_FLOAT:
					$result[1] = $value;
					break;
				case Attribute::TYPE_DATETIME:
					$result[2] = is_string($value) ? new \DateTime($value) : $value;
					break;
				case Attribute::TYPE_CODE:
					$result[3] = $value;
					break;
				case Attribute::TYPE_TEXT:
					$result[4] = (is_array($value)) ? (isset($value['t']) ? $value['t'] : null) : $value;
					break;
			}
		}
		return $result;
	}

	/**
	 * @param integer $productId
	 * @param array $value
	 * @return integer
	 */
	protected function insertAttributeValue($productId, $value)
	{
		$valueType = $value['valueType'];
		$attributeId = $value['id'];
		list($integerValue, $floatValue, $dateValue, $stringValue, $textValue) = $this->dispatchValue($valueType,
			$value['value']);
		$qb = $this->getDbProvider()->getNewStatementBuilder('insertAttributeValue');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->insert($fb->table('rbs_catalog_dat_attribute'),
				$fb->column('product_id'), $fb->column('attribute_id'),
				$fb->column('integer_value'), $fb->column('float_value'), $fb->column('date_value'),
				$fb->column('string_value'), $fb->column('text_value'));
			$qb->addValues($fb->integerParameter('productId'), $fb->integerParameter('attributeId'),
				$fb->integerParameter('integerValue'), $fb->decimalParameter('floatValue'), $fb->dateTimeParameter('dateValue'),
				$fb->parameter('stringValue'), $fb->lobParameter('textValue'));
		}
		$is = $qb->insertQuery();
		$is->bindParameter('productId', $productId)->bindParameter('attributeId', $attributeId)
			->bindParameter('integerValue', $integerValue)->bindParameter('floatValue', $floatValue)
			->bindParameter('dateValue', $dateValue)
			->bindParameter('stringValue', $stringValue)->bindParameter('textValue', $textValue);
		$is->execute();
		return $is->getDbProvider()->getLastInsertId('rbs_catalog_dat_attribute');
	}

	/**
	 * @param integer $attrId
	 * @param array $value
	 * @return integer
	 */
	protected function updateAttributeValue($attrId, $value)
	{
		$valueType = $value['valueType'];
		list($integerValue, $floatValue, $dateValue, $stringValue, $textValue) = $this->dispatchValue($valueType,
			$value['value']);
		$qb = $this->getDbProvider()->getNewStatementBuilder('updateAttributeValue');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->update($fb->table('rbs_catalog_dat_attribute'));
			$qb->assign($fb->column('integer_value'), $fb->integerParameter('integerValue'));
			$qb->assign($fb->column('float_value'), $fb->decimalParameter('floatValue'));
			$qb->assign($fb->column('date_value'), $fb->dateTimeParameter('dateValue'));
			$qb->assign($fb->column('string_value'), $fb->parameter('stringValue'));
			$qb->assign($fb->column('text_value'), $fb->lobParameter('textValue'));
			$qb->where($fb->eq($fb->column('id'), $fb->integerParameter('attrId')));
		}
		$uq = $qb->updateQuery();
		$uq->bindParameter('integerValue', $integerValue)->bindParameter('floatValue', $floatValue)
			->bindParameter('dateValue', $dateValue)
			->bindParameter('stringValue', $stringValue)->bindParameter('textValue', $textValue)
			->bindParameter('attrId', $attrId);
		$uq->execute();
	}

	/**
	 * @param integer $productId
	 * @param array $excludeAttrIds
	 */
	protected function deleteAttributeValue($productId, $excludeAttrIds = array())
	{
		$qb = $this->getDbProvider()->getNewStatementBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->delete($fb->table('rbs_catalog_dat_attribute'));
		if (count($excludeAttrIds))
		{
			$notIn = array();
			foreach ($excludeAttrIds as $id)
			{
				$notIn[] = $fb->number($id);
			}
			$qb->where($fb->logicAnd(
				$fb->eq($fb->column('product_id'), $fb->integerParameter('productId')),
				$fb->notIn($fb->column('id'), $notIn)
			));
		}
		else
		{
			$qb->where($fb->eq($fb->column('product_id'), $fb->integerParameter('productId')));
		}
		$dq = $qb->deleteQuery();
		$dq->bindParameter('productId', $productId);
		$dq->execute();
	}

	/**
	 * @param Attribute $attribute
	 * @return array|null
	 */
	public function buildEditorDefinition(Attribute $attribute)
	{
		if ($attribute->getValueType() === Attribute::TYPE_GROUP)
		{
			$definition = array('attributes' => array());
			$ids = array($attribute->getId());
			foreach ($attribute->getAttributes() as $childAttribute)
			{
				$ids[] = $childAttribute->getId();
				if ($childAttribute->getValueType() === Attribute::TYPE_GROUP)
				{
					$defGroup = $this->buildGroupDefinition($childAttribute, $ids);
					if (count($defGroup['attributes']))
					{
						$definition['attributes'][] = $defGroup;
					}
				}
				else
				{
					$def = $this->buildAttributeDefinition($childAttribute);
					if ($def)
					{
						$definition['attributes'][] = $def;
					}
				}
			}
			if (count($definition['attributes']))
			{
				$definition['ids'] = $ids;
				return $definition;
			}
		}
		return null;
	}

	/**
	 * @param Attribute $attribute
	 * @param $ids
	 * @return array
	 */
	public function buildGroupDefinition($attribute, &$ids)
	{
		$definition = array('label' => $attribute->getLabel(), 'attributes' => array());
		foreach ($attribute->getAttributes() as $childAttribute)
		{
			if (!in_array($childAttribute->getId(), $ids))
			{
				$ids[] = $childAttribute->getId();
				if ($childAttribute->getValueType() === Attribute::TYPE_GROUP)
				{
					$groupDef = $this->buildGroupDefinition($childAttribute, $ids);
					$definition['attributes'] = array_merge($definition['attributes'], $groupDef['attributes']);
				}
				else
				{
					$def = $this->buildAttributeDefinition($childAttribute);
					if ($def)
					{
						$definition['attributes'][] = $def;
					}
				}
			}
		}
		return $definition;
	}

	/**
	 * @param Attribute $attribute
	 * @return array|null
	 */
	public function buildAttributeDefinition($attribute)
	{
		$vt = $attribute->getValueType();
		$definition = array('id' => $attribute->getId(), 'label' => $attribute->getLabel(),
			'required' => $attribute->getRequiredValue(), 'valueType' => $vt, 'type' => $vt,
			'defaultValue' => null, 'collectionCode' => null, 'values' => null);

		if (Attribute::TYPE_PROPERTY == $vt)
		{
			$property = $attribute->getModelProperty();
			if (!$property)
			{
				return null;
			}
			$propertyName = $property->getName();

			$definition['propertyName']  = $propertyName;
			switch ($property->getType())
			{
				case \Change\Documents\Property::TYPE_DOCUMENT :
				case \Change\Documents\Property::TYPE_DOCUMENTID :
				case \Change\Documents\Property::TYPE_DOCUMENTARRAY :
					$definition['type'] = $property->getType();
					$definition['documentType'] = ($property->getDocumentType()) ? $property->getDocumentType() : '';
					break;
				case \Change\Documents\Property::TYPE_STRING :
					$definition['type'] = Attribute::TYPE_CODE;
					break;
				case \Change\Documents\Property::TYPE_BOOLEAN :
					$definition['type'] = Attribute::TYPE_BOOLEAN;
					break;
				case \Change\Documents\Property::TYPE_INTEGER :
					$definition['type'] = Attribute::TYPE_INTEGER;
					break;
				case \Change\Documents\Property::TYPE_FLOAT :
				case \Change\Documents\Property::TYPE_DECIMAL :
					$definition['type'] = Attribute::TYPE_FLOAT;
					break;
				case \Change\Documents\Property::TYPE_DATE :
				case \Change\Documents\Property::TYPE_DATETIME :
					$definition['type'] = Attribute::TYPE_DATETIME;
					break;
				case \Change\Documents\Property::TYPE_RICHTEXT :
					$definition['type'] = Attribute::TYPE_TEXT;
					break;
				default:
					return null;
			}
		}
		elseif (Attribute::TYPE_DOCUMENTID == $vt || Attribute::TYPE_DOCUMENTIDARRAY == $vt)
		{
			$definition['documentType'] = ($attribute->getDocumentType()) ? $attribute->getDocumentType() : '';
		}

		if (($dv = $attribute->getDefaultValue()) !== null)
		{
			if ($vt === Attribute::TYPE_BOOLEAN)
			{
				$definition['defaultValue'] = ($dv == '1' || $dv == 'true');
			}
			elseif ($vt === Attribute::TYPE_INTEGER)
			{
				$definition['defaultValue'] = intval($dv);
			}
			elseif ($vt === Attribute::TYPE_FLOAT)
			{
				$definition['defaultValue'] = floatval($dv);
			}
			elseif ($vt === Attribute::TYPE_DATETIME)
			{
				$definition['defaultValue'] = (new \DateTime($dv))->format(\DateTime::ISO8601);
			}
			elseif ($vt === Attribute::TYPE_CODE)
			{
				$definition['defaultValue'] = $dv;
			}
		}

		if (in_array($vt, array(Attribute::TYPE_INTEGER, Attribute::TYPE_CODE, Attribute::TYPE_DOCUMENTID)) && $attribute->getCollectionCode())
		{
			$definition['values'] = $this->getCollectionValues($attribute);
			if (is_array($definition['values']))
			{
				$definition['collectionCode'] = $attribute->getCollectionCode();
			}
		}
		return $definition;
	}

	/**
	 * Use CollectionManager
	 * @param Attribute $attribute
	 * @return array|null
	 */
	public function getCollectionValues($attribute)
	{
		$cm = $this->getCollectionManager();
		if ($cm && $attribute instanceof Attribute && $attribute->getCollectionCode())
		{
			$collection = $cm->getCollection($attribute->getCollectionCode());
			if ($collection)
			{
				$values = array();
				foreach($collection->getItems() as $item)
				{
					$values[] = array('value' => $item->getValue(), 'label' => $item->getLabel(), 'title' => $item->getTitle());
				}
				return $values;
			}
		}
		return null;
	}

	/**
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @param array $attributeValues
	 * @return null
	 * @return array
	 */
	public function normalizeRestAttributeValues(\Rbs\Catalog\Documents\Product $product, $attributeValues)
	{
		$normalizedValues = array();
		if (is_array($attributeValues) && count($attributeValues))
		{
			$utcTimeZone = new \DateTimeZone('UTC');
			$documentManager = $this->getDocumentManager();
			foreach ($attributeValues as $attributeValue)
			{
				$id = intval($attributeValue['id']);
				$attribute = $documentManager->getDocumentInstance($id);
				if (!$attribute instanceof Attribute)
				{
					continue;
				}
				$valueType = $attribute->getValueType();
				if ($valueType === Attribute::TYPE_PROPERTY)
				{
					//Property Attribute has no value
					$normalizedValues[] = array('id' => $id, 'valueType' => $valueType);
					continue;
				}

				$value = isset($attributeValue['value']) ? $attributeValue['value'] : null;
				if ($value === null)
				{
					//null value no need conversion
					$normalizedValues[] = array('id' => $id, 'valueType' => $valueType, 'value' => $value);
					continue;
				}

				switch ($valueType)
				{
					case Attribute::TYPE_DOCUMENTID:
						if (is_array($value) && isset($value['id']))
						{
							$value = $value['id'];
						}

						if (is_numeric($value) && $value > 0)
						{
							$value = intval($value);
						}
						else
						{
							$value = null;
						}
						break;
					case Attribute::TYPE_DOCUMENTIDARRAY:
						if (is_array($value))
						{
							$ids = array();
							foreach ($value as $docId)
							{
								if (is_array($docId) && isset($docId['id']))
								{
									$docId = $docId['id'];
								}

								if (is_numeric($docId) && $docId > 0)
								{
									$ids[] = intval($docId);
								}
							}
							$value = count($ids) ? $ids : null;
						}
						else
						{
							$value = null;
						}
						break;
					case Attribute::TYPE_DATETIME:
						$value = (new \DateTime($value, $utcTimeZone))->format(\DateTime::ISO8601);
						break;
					case Attribute::TYPE_TEXT:
						$value = (new \Change\Documents\RichtextProperty($value))->toArray();
						break;
				}
				$normalizedValues[] = array('id' => $id, 'valueType' => $valueType, 'value' => $value);
			}
		}
		if (count($normalizedValues))
		{
			return $normalizedValues;
		}
		else
		{
			$this->deleteAttributeValue($product->getId());
			return null;
		}
	}

	/**
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @param array $attributeValues
	 * @param \Change\Http\UrlManager $urlManager
	 * @return array
	 */
	public function expandAttributeValues($product, $attributeValues, $urlManager)
	{
		$expandedAttributeValues = array();
		if (is_array($attributeValues) && count($attributeValues))
		{
			$documentManager = $this->getDocumentManager();
			$valueConverter = new \Change\Http\Rest\ValueConverter($urlManager, $documentManager);
			foreach ($attributeValues as  $attributeValue)
			{
				$id = intval($attributeValue['id']);
				$attribute = $documentManager->getDocumentInstance($id);
				if (!$attribute instanceof Attribute)
				{
					continue;
				}

				$valueType = $attribute->getValueType();
				$value = $attributeValue['value'];

				switch ($valueType)
				{
					case Attribute::TYPE_PROPERTY:
						$attribute = $documentManager->getDocumentInstance($id);
						if ($attribute instanceof Attribute)
						{
							$property = $attribute->getModelProperty();
							if ($property)
							{
								$pc = new \Change\Http\Rest\PropertyConverter($product, $property, $documentManager, $urlManager);
								$value = $pc->getRestValue();
							}
						}
						break;

					case Attribute::TYPE_DOCUMENTID:
						if ($value !== null)
						{
							$document = $documentManager->getDocumentInstance($value);
							$value = $valueConverter->toRestValue($document, \Change\Documents\Property::TYPE_DOCUMENT);
						}
						break;
					case Attribute::TYPE_DATETIME:
						if ($value !== null)
						{
							$value = $valueConverter->toRestValue(new \DateTime($value), \Change\Documents\Property::TYPE_DATETIME);
						}
						break;
				}
				$expandedAttributeValues[] = array('id' => $id, 'valueType' => $valueType, 'value' => $value);
			}
		}
		return count($expandedAttributeValues) ? $expandedAttributeValues : null;
	}

	/**
	 * @param Attribute $groupAttribute
	 * @return Attribute[]
	 */
	public function getAxisAttributes($groupAttribute)
	{
		$axeAttributes = array();
		if ($groupAttribute instanceof Attribute && $groupAttribute->getValueType() === Attribute::TYPE_GROUP);
		{
			foreach ($groupAttribute->getAttributes() as $axeAttribute)
			{
				if ($axeAttribute->getValueType() === Attribute::TYPE_GROUP )
				{
					$axeAttributes = array_merge($axeAttributes, $this->getAxisAttributes($axeAttribute));
				}
				elseif ($axeAttribute->isVisibleFor('axes'))
				{
					$axeAttributes[] = $axeAttribute;
				}
			}
		}
		return $axeAttributes;
	}

	/**
	 * @param string $visibility
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @return array
	 */
	public function getProductAttributesConfiguration($visibility, $product)
	{
		if (!($product instanceof \Rbs\Catalog\Documents\Product))
		{
			return array();
		}
		$groupAttribute = $product->getAttribute();
		if (!$groupAttribute || !$groupAttribute->getAttributesCount())
		{
			return array();
		}

		$attributeValues = $product->getCurrentLocalization()->getAttributeValues();
		if (!is_array($attributeValues))
		{
			$attributeValues = array();
		}

		$configuration = array('global' => array('items' => array()));
		foreach ($groupAttribute->getAttributes() as $attribute)
		{
			if (!$attribute->isVisibleFor($visibility))
			{
				continue;
			}

			if ($attribute->getAttributesCount())
			{
				$title = $attribute->getCurrentLocalization()->getTitle();
				$configuration[$attribute->getId()] = array('title' => $title,
					'items' => $this->generateItems($attribute, $visibility, $product, $attributeValues));
			}
			else
			{
				$item = $this->generateItem($attribute, $product, $attributeValues);
				if ($item)
				{
					$configuration['global']['items'][$attribute->getId()] = $item;
				}
			}
		}

		if (count($configuration['global']['items']))
		{
			$i18n = $this->getI18nManager();
			$configuration['global']['title'] = $i18n->trans('m.rbs.catalog.front.main_attributes', array('ucf'));
		}
		else
		{
			unset($configuration['global']);
		}
		return $configuration;
	}

	/**
	 * @param \Rbs\Catalog\Documents\Attribute $group
	 * @param string $visibility
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @param array $attributeValues
	 * @return array
	 */
	protected function generateItems(\Rbs\Catalog\Documents\Attribute $group, $visibility, $product, $attributeValues)
	{
		$items = array();
		foreach ($group->getAttributes() as $attribute)
		{
			if (!$attribute->isVisibleFor($visibility))
			{
				continue;
			}
			if ($attribute->getAttributesCount())
			{
				$items = array_merge($items, $this->generateItems($attribute, $visibility, $product, $attributeValues));
			}
			else
			{
				$item = $this->generateItem($attribute, $product, $attributeValues);
				if ($item)
				{
					$items[$attribute->getId()] = $item;
				}
			}
		}
		return $items;
	}

	/**
	 * @param \Rbs\Catalog\Documents\Attribute $attribute
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @param array $attributeValues
	 * @return array
	 */
	protected function generateItem(\Rbs\Catalog\Documents\Attribute $attribute, $product, $attributeValues)
	{
		$attributeId = $attribute->getId();
		$value = array_reduce($attributeValues, function($result, $attrVal) use ($attributeId) {
			return $attributeId == $attrVal['id'] ? $attrVal['value'] : $result;
		});

		$valueType = $attribute->getValueType();
		switch ($valueType)
		{
			case \Rbs\Catalog\Documents\Attribute::TYPE_PROPERTY:
				if ($product)
				{
					$property = $attribute->getModelProperty();
					if ($property)
					{
						$valueType = $this->getAttributeTypeFromProperty($property);
						$value = $property->getValue($product);
					}
				}
				if ($valueType == \Rbs\Catalog\Documents\Attribute::TYPE_PROPERTY)
				{
					$valueType = \Rbs\Catalog\Documents\Attribute::TYPE_TEXT;
					$value = strval($value);
				}
				break;

			case \Rbs\Catalog\Documents\Attribute::TYPE_DOCUMENTID:
				if ($value !== null)
				{
					$value = $this->getDocumentManager()->getDocumentInstance($value);
				}
				break;

			case \Rbs\Catalog\Documents\Attribute::TYPE_DOCUMENTIDARRAY:
				$documents = array();
				if (is_array($value))
				{
					foreach ($value as $id)
					{
						$d = $this->getDocumentManager()->getDocumentInstance($id);
						if ($d)
						{
							$documents[] = $d;
						}
					}
					$value = $documents;
				}
				else
				{
					$value = $documents;
				}
				break;

			case \Rbs\Catalog\Documents\Attribute::TYPE_DATETIME:
				if ($value !== null)
				{
					$value = new \DateTime($value);
				}
				break;

			case \Rbs\Catalog\Documents\Attribute::TYPE_TEXT:
				if ($value !== null)
				{
					$value = new \Change\Documents\RichtextProperty($value);
				}
				break;
		}

		if ($value)
		{
			return $this->renderItem($attribute, $value, $valueType);
		}
		return null;
	}

	/**
	 * @param \Change\Documents\Property $property
	 * @return string
	 */
	protected function getAttributeTypeFromProperty($property)
	{
		switch ($property->getType())
		{
			case \Change\Documents\Property::TYPE_DOCUMENT :
			case \Change\Documents\Property::TYPE_DOCUMENTID :
				return \Rbs\Catalog\Documents\Attribute::TYPE_DOCUMENTID;
			case \Change\Documents\Property::TYPE_DOCUMENTARRAY :
				return \Rbs\Catalog\Documents\Attribute::TYPE_DOCUMENTIDARRAY;
			case \Change\Documents\Property::TYPE_STRING :
				return \Rbs\Catalog\Documents\Attribute::TYPE_CODE;
			case \Change\Documents\Property::TYPE_BOOLEAN :
				return \Rbs\Catalog\Documents\Attribute::TYPE_BOOLEAN;
			case \Change\Documents\Property::TYPE_INTEGER :
				return \Rbs\Catalog\Documents\Attribute::TYPE_INTEGER;
			case \Change\Documents\Property::TYPE_FLOAT :
			case \Change\Documents\Property::TYPE_DECIMAL :
				return \Rbs\Catalog\Documents\Attribute::TYPE_FLOAT;
			case \Change\Documents\Property::TYPE_DATE :
			case \Change\Documents\Property::TYPE_DATETIME :
				return \Rbs\Catalog\Documents\Attribute::TYPE_DATETIME;
			case \Change\Documents\Property::TYPE_RICHTEXT :
				return \Rbs\Catalog\Documents\Attribute::TYPE_TEXT;
			default:
				return null;
		}
	}

	/**
	 * @param \Rbs\Catalog\Documents\Attribute $attribute
	 * @param mixed $value
	 * @param string $valueType
	 * @return array
	 */
	protected function renderItem($attribute, $value, $valueType)
	{
		$title = $attribute->getCurrentLocalization()->getTitle();
		$item = array('title' => $title, 'value' => $value, 'valueType' => $valueType);

		$description = $attribute->getCurrentLocalization()->getDescription();
		if ($description && !$description->isEmpty())
		{
			$item['description'] = $description;
		}

		switch ($item['valueType'])
		{
			case \Rbs\Catalog\Documents\Attribute::TYPE_DOCUMENTID:
				if (!$this->isValidDocument($value))
				{
					return null;
				}
				$item['template'] = 'Rbs_Catalog/Blocks/Attribute/document.twig';
				break;

			case \Rbs\Catalog\Documents\Attribute::TYPE_DOCUMENTIDARRAY:
				foreach ($value as $index => $document)
				{
					if (!$this->isValidDocument($document))
					{
						unset($value[$index]);
					}
				}
				if (count($value) < 1)
				{
					return null;
				}
				$item['template'] = 'Rbs_Catalog/Blocks/Attribute/documentarray.twig';
				break;

			case \Rbs\Catalog\Documents\Attribute::TYPE_DATETIME:
				$item['template'] = 'Rbs_Catalog/Blocks/Attribute/datetime.twig';
				break;

			case \Rbs\Catalog\Documents\Attribute::TYPE_BOOLEAN:
				$item['template'] = 'Rbs_Catalog/Blocks/Attribute/boolean.twig';
				break;

			case \Rbs\Catalog\Documents\Attribute::TYPE_FLOAT:
				$item['template'] = 'Rbs_Catalog/Blocks/Attribute/float.twig';
				break;

			case \Rbs\Catalog\Documents\Attribute::TYPE_INTEGER:
				$value = $this->getCollectionItemTitle($attribute->getCollectionCode(), $value);
				if ($value !== false)
				{
					$item['value'] = $value;
					$item['template'] = 'Rbs_Catalog/Blocks/Attribute/text.twig';
				}
				else
				{
					$item['template'] = 'Rbs_Catalog/Blocks/Attribute/integer.twig';
				}
				break;

			case \Rbs\Catalog\Documents\Attribute::TYPE_TEXT:
				$item['template'] = 'Rbs_Catalog/Blocks/Attribute/richtext.twig';
				break;

			default:
				$value = $this->getCollectionItemTitle($attribute->getCollectionCode(), $value);
				if ($value !== false)
				{
					$item['value'] = $value;
				}
				$item['template'] = 'Rbs_Catalog/Blocks/Attribute/text.twig';
				break;
		}
		return $item;
	}

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @return boolean
	 */
	protected function isValidDocument($document)
	{
		if ($document instanceof \Change\Documents\Interfaces\Publishable)
		{
			return $document->published();
		}
		elseif ($document instanceof \Change\Documents\Interfaces\Activable)
		{
			return $document->activated();
		}
		return true;
	}


	/**
	 * @param string $collectionCode
	 * @param string $value
	 * @return string|boolean
	 */
	protected function getCollectionItemTitle($collectionCode, $value)
	{
		if (is_string($collectionCode))
		{
			$c = $this->getCollectionManager()->getCollection($collectionCode);
			if ($c)
			{
				$i = $c->getItemByValue($value);
				if ($i)
				{
					return $i->getTitle();
				}
			}
		}
		return false;
	}

}