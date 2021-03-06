<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Generic\Json;

use Change\Documents\AbstractDocument;

/**
 * @name \Rbs\Generic\Json\Import
 */
class Import
{
	/**
	 * @var integer|string|null
	 */
	protected $contextId;

	/**
	 * @var \Zend\Stdlib\Parameters
	 */
	protected $options;

	/**
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;

	/**
	 * @var \Change\Documents\modelManager
	 */
	protected $modelManager;

	/**
	 * @var \Change\Documents\DocumentCodeManager
	 */
	protected $documentCodeManager;

	/**
	 * @var array
	 */
	protected $ignoredProperties = ['id', 'model', 'refLCID', 'LCID', 'modificationDate', 'documentVersion', 'authorId'];

	/**
	 * @var AbstractDocument[]
	 */
	protected $imported = [];

	/**
	 * @var string[]
	 */
	protected $codes = [];

	/**
	 * @var \Rbs\Generic\Json\JsonConverter
	 */
	protected $valueConverter;

	/**
	 * @var boolean
	 */
	protected $addOnly = false;

	/**
	 * @param \Change\Documents\DocumentManager $documentManager
	 */
	public function __construct(\Change\Documents\DocumentManager $documentManager)
	{
		$this->documentManager = $documentManager;
	}

	/**
	 * @return \Change\Documents\DocumentManager
	 */
	protected function getDocumentManager()
	{
		return $this->documentManager;
	}

	/**
	 * @return \Change\Documents\modelManager
	 */
	protected function getModelManager()
	{
		if ($this->modelManager === null)
		{
			$this->modelManager = $this->getDocumentManager()->getModelManager();
		}
		return $this->modelManager;
	}

	/**
	 * @param \Change\Documents\DocumentCodeManager $documentCodeManager
	 * @return $this
	 */
	public function setDocumentCodeManager(\Change\Documents\DocumentCodeManager $documentCodeManager)
	{
		$this->documentCodeManager = $documentCodeManager;
		return $this;
	}

	/**
	 * @return \Change\Documents\DocumentCodeManager
	 */
	protected function getDocumentCodeManager()
	{
		return $this->documentCodeManager;
	}

	/**
	 * @return \Zend\Stdlib\Parameters
	 */
	public function getOptions()
	{
		if ($this->options === null)
		{
			$this->options = new \Zend\Stdlib\Parameters();
		}
		return $this->options;
	}

	/**
	 * @param boolean $addOnly
	 * @return $this
	 */
	public function addOnly($addOnly)
	{
		$this->addOnly = $addOnly == true;
		return $this;
	}

	/**
	 * @param integer|string|null $contextId
	 * @return $this
	 */
	public function setContextId($contextId)
	{
		$this->contextId = $contextId;
		return $this;
	}

	/**
	 * @return integer|string|null
	 */
	public function getContextId()
	{
		return $this->contextId;
	}

	/**
	 * @param array $ignoredProperties
	 * @return $this
	 */
	public function setIgnoredProperties(array $ignoredProperties)
	{
		$this->ignoredProperties = $ignoredProperties;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getIgnoredProperties()
	{
		return $this->ignoredProperties;
	}

	/**
	 * @return string[]
	 */
	public function getCodes()
	{
		return $this->codes;
	}

	/**
	 * @return \Change\Documents\AbstractDocument[]
	 */
	public function getImported()
	{
		return $this->imported;
	}

	/**
	 * @param \Rbs\Generic\Json\JsonConverter $valueConverter
	 * @return $this
	 */
	public function setValueConverter($valueConverter)
	{
		$this->valueConverter = $valueConverter;
		return $this;
	}

	/**
	 * @return \Rbs\Generic\Json\JsonConverter
	 */
	public function getValueConverter()
	{
		if ($this->valueConverter === null) {
			$this->valueConverter = new \Rbs\Generic\Json\JsonConverter();
		}
		return $this->valueConverter;
	}

	/**
	 * @param array $json
	 * @return array
	 */
	public function fromArray(array $json)
	{
		$this->imported = [];
		$this->codes = [];
		if (isset($json['contextId'])) {
			$this->setContextId($json['contextId']);
		}
		if (isset($json['documents']) && is_array($json['documents']))
		{
			foreach ($json['documents'] as $jsonDocument)
			{
				$document = $this->resolveDocument($jsonDocument);
				if ($document)
				{
					$this->import($document, $jsonDocument);
				}
			}
		}
		return $this->imported;
	}

	/**
	 * @param array $jsonDocument
	 * @return AbstractDocument|null
	 */
	protected function resolveDocument(array $jsonDocument)
	{
		if (!isset($jsonDocument['_id']))
		{
			return null;
		}

		$id = $jsonDocument['_id'];
		if (!isset($jsonDocument['_model']))
		{
			if (isset($this->imported[$id]))
			{
				return $this->imported[$id];
			}
			$callback = $this->getOptions()->get('resolveDocument');
			if (is_callable($callback))
			{
				return call_user_func($callback, $id, $this->getContextId());
			}
		}
		elseif ($this->getContextId() !== null)
		{
			$model = $this->getModelManager()->getModelByName($jsonDocument['_model']);
			$docs = $this->getDocumentCodeManager()->getDocumentsByCode($id, $this->getContextId());
			foreach ($docs as $doc)
			{
				if ($doc->getDocumentModelName() === $model->getName())
				{
					$this->imported[$id] = $doc;
					$this->codes[$doc->getId()] = $id;
					return $doc;
				}
			}
			$doc = $this->getDocumentManager()->getNewDocumentInstanceByModel($model);
			$this->imported[$id] = $doc;
			return $doc;
		}
		else
		{
			$model = $this->getModelManager()->getModelByName($jsonDocument['_model']);
			$doc = $this->getDocumentManager()->getDocumentInstance($id);
			if ($doc && $doc->getDocumentModelName() === $model->getName())
			{
				$this->imported[$id] = $doc;
				return $doc;
			}
		}
		return null;
	}

	/**
	 * @param AbstractDocument $document
	 * @param $code
	 */
	protected function setContextCode(AbstractDocument $document, $code)
	{
		if (!isset($this->codes[$document->getId()]))
		{
			$this->codes[$document->getId()] = $code;
			if ($this->getContextId() !== null)
			{
				$this->getDocumentCodeManager()->addDocumentCode($document, $code, $this->getContextId());
			}
		}
	}

	/**
	 * @param AbstractDocument $document
	 * @param array $jsonDocument
	 */
	protected function import(AbstractDocument $document, array $jsonDocument)
	{
		if (!isset($jsonDocument['_model']))
		{
			return;
		}
		if ($this->addOnly && !$document->isNew())
		{
			return;
		}
		$model = $document->getDocumentModel();
		foreach ($model->getProperties() as $property)
		{
			$propertyName = $property->getName();
			if ($property->getLocalized() || !array_key_exists($propertyName, $jsonDocument))
			{
				continue;
			}
			$value = $jsonDocument[$propertyName];
			if ($property->getType() === \Change\Documents\Property::TYPE_DOCUMENT)
			{
				$subDoc = null;
				if (is_array($value))
				{
					$subDoc = $this->resolveDocument($value);
					if ($subDoc)
					{
						$this->import($subDoc, $value);
					}
				}
				$property->setValue($document, $subDoc);
			}
			elseif ($property->getType() === \Change\Documents\Property::TYPE_DOCUMENTARRAY)
			{
				$docs = [];
				if (is_array($value))
				{
					foreach ($value as $jsonSubDocument)
					{
						if (is_array($jsonSubDocument))
						{
							$subDoc = $this->resolveDocument($jsonSubDocument);
							if ($subDoc)
							{
								$this->import($subDoc, $jsonSubDocument);
								$docs[] = $subDoc;
							}
						}
					}
				}
				$property->setValue($document, $docs);
			}
			else
			{
				$property->setValue($document, $this->getValueConverter()->toPropertyValue($value, $property->getType()));
			}
		}

		$callback = $this->getOptions()->get('preSave');
		if (is_callable($callback))
		{
			call_user_func($callback, $document, $jsonDocument);
		}

		/** @var $document \Change\Documents\Interfaces\Localizable|AbstractDocument */
		if ($document instanceof \Change\Documents\Interfaces\Localizable)
		{
			if (isset($jsonDocument['_LCID']) && is_array($jsonDocument['_LCID']))
			{
				foreach ($jsonDocument['_LCID'] as $LCID => $jsonLCID)
				{
					try
					{
						$this->getDocumentManager()->pushLCID($LCID);
						if ($document->getRefLCID() === null)
						{
							$document->setRefLCID($LCID);
						}

						$this->importLCID($model, $document->getCurrentLocalization(), $jsonLCID);
						$document->save();
						$this->setContextCode($document, $jsonDocument['_id']);
						$this->getDocumentManager()->popLCID();
					}
					catch (\Exception $e)
					{
						$this->getDocumentManager()->popLCID($e);
					}
				}
			}
		}
		else
		{
			$document->save();
			$this->setContextCode($document, $jsonDocument['_id']);
		}
	}

	/**
	 * @param \Change\Documents\AbstractModel $model
	 * @param \Change\Documents\AbstractLocalizedDocument $document
	 * @param array $jsonLCID
	 */
	protected function importLCID($model, \Change\Documents\AbstractLocalizedDocument $document, array $jsonLCID)
	{
		foreach ($model->getProperties() as $property)
		{
			$propertyName = $property->getName();
			if (!$property->getLocalized() || !array_key_exists($propertyName, $jsonLCID))
			{
				continue;
			}
			$value = $jsonLCID[$propertyName];
			$property->setLocalizedValue($document, $this->getValueConverter()->toPropertyValue($value, $property->getType()));
		}
	}
} 