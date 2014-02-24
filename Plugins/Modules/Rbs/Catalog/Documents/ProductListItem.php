<?php
namespace Rbs\Catalog\Documents;

use Change\Http\Rest\Result\DocumentLink;
use Change\Http\Rest\Result\Link;

/**
 * @name \Rbs\Catalog\Documents\ProductListItem
 */
class ProductListItem extends \Compilation\Rbs\Catalog\Documents\ProductListItem
{
	/**
	 * @return string
	 */
	public function getLabel()
	{
		return $this->getProduct()->getLabel();
	}

	/**
	 * @param string $label
	 * @return $this
	 */
	public function setLabel($label)
	{
		// Nothing to do...
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function isHighlighted()
	{
		return $this->getPosition() < 0;
	}

	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onDefaultUpdateRestResult(\Change\Documents\Events\Event $event)
	{
		parent::onDefaultUpdateRestResult($event);
		$restResult = $event->getParam('restResult');
		if ($restResult instanceof \Change\Http\Rest\Result\DocumentResult)
		{
			/* @var $document \Rbs\Catalog\Documents\ProductListItem */
			$document = $restResult->getDocument();
			$urlManager = $restResult->getUrlManager();
			$restResult->setProperty('productListId', $document->getProductListId());

			/* @var $selfLink DocumentLink */
			$selfLink = $restResult->getRelLink('self')[0];
			$pathInfo = $selfLink->getPathInfo();
			if ($this->isHighlighted())
			{
				$restResult->addAction(new Link($urlManager, $pathInfo . '/downplay', 'downplay'));
			}
			else
			{
				$restResult->addAction(new Link($urlManager, $pathInfo . '/highlight', 'highlight'));
			}
			$restResult->addAction(new Link($urlManager, $pathInfo . '/moveup', 'moveup'));
			$restResult->addAction(new Link($urlManager, $pathInfo . '/movedown', 'movedown'));
			$restResult->addAction(new Link($urlManager, $pathInfo . '/highlighttop', 'highlighttop'));
			$restResult->addAction(new Link($urlManager, $pathInfo . '/highlightbottom', 'highlightbottom'));
		}
		elseif ($restResult instanceof \Change\Http\Rest\Result\DocumentLink)
		{
			/* @var $document \Rbs\Catalog\Documents\ProductListItem */
			$document = $restResult->getDocument();
			$urlManager = $restResult->getUrlManager();

			$product = $document->getProduct();
			if ($product instanceof \Rbs\Catalog\Documents\Product)
			{
				$restResult->setProperty('product', new DocumentLink($urlManager, $product, DocumentLink::MODE_PROPERTY));
			}
			$productList = $this->getProductList();
			if ($productList instanceof \Rbs\Catalog\Documents\ProductList)
			{
				$restResult->setProperty('productList',
					new DocumentLink($urlManager, $productList, DocumentLink::MODE_PROPERTY));
			}

			$restResult->setProperty('isHighlighted', $document->isHighlighted());
			$restResult->setProperty('position', $document->getPosition());
			$restResult->setProperty('productListId', $document->getProductListId());

			$pathInfo = $restResult->getPathInfo();

			if ($this->isHighlighted())
			{
				$actions[] = (new Link($urlManager, $pathInfo . '/downplay', 'downplay'))->toArray();
			}
			else
			{
				$actions[] = (new Link($urlManager, $pathInfo . '/highlight', 'highlight'))->toArray();
			}
			$actions[] = (new Link($urlManager, $pathInfo . '/moveup', 'moveup'))->toArray();
			$actions[] = (new Link($urlManager, $pathInfo . '/movedown', 'movedown'))->toArray();
			$actions[] = (new Link($urlManager, $pathInfo . '/highlighttop', 'highlighttop'))->toArray();
			$actions[] = (new Link($urlManager, $pathInfo . '/highlightbottom', 'highlightbottom'))->toArray();
			$restResult->setProperty('actions', $actions);
		}
	}

	/**
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 */
	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach(\Change\Documents\Events\Event::EVENT_CREATED, array($this, 'onCreated'), 5);
		$eventManager->attach(\Change\Documents\Events\Event::EVENT_DELETED, array($this, 'onDeleted'), 5);
	}

	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onCreated(\Change\Documents\Events\Event $event)
	{
		// Section product list synchronization.
		$list = $this->getProductList();
		$product = $this->getProduct();
		if ($list instanceof \Rbs\Catalog\Documents\SectionProductList && $product instanceof \Rbs\Catalog\Documents\Product)
		{
			$section = $list->getSynchronizedSection();
			if ($section && !in_array($section->getId(), $product->getPublicationSectionsIds()))
			{
				$product->getPublicationSections()->add($section);
				$product->save();
			}
		}
		elseif ($list instanceof \Rbs\Catalog\Documents\CrossSellingProductList
			&& $product instanceof \Rbs\Catalog\Documents\Product
		)
		{
			//CrossSellingList Symmetry
			if ($list->getSymmetrical())
			{
				$jm = $event->getApplicationServices()->getJobManager();
				$jm->createNewJob('Rbs_Catalog_UpdateSymmetricalProductListItem',
					array('listId' => $list->getId(), 'productId' => $product->getId(), 'action' => 'add'));
			}
		}
	}

	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onDeleted(\Change\Documents\Events\Event $event)
	{
		// Section product list synchronization.
		$product = $this->getProduct();
		$list = $this->getProductList();
		if ($list instanceof \Rbs\Catalog\Documents\SectionProductList && $product instanceof \Rbs\Catalog\Documents\Product)
		{
			$section = $list->getSynchronizedSection();
			if ($section && in_array($section->getId(), $product->getPublicationSectionsIds()))
			{
				$product->getPublicationSections()->remove($section);
				$product->save();
			}
		}
		elseif ($list instanceof \Rbs\Catalog\Documents\CrossSellingProductList
			&& $product instanceof \Rbs\Catalog\Documents\Product
		)
		{
			//CrossSellingList Symmetry
			if ($list->getSymmetrical())
			{
				$jm = $event->getApplicationServices()->getJobManager();
				$jm->createNewJob('Rbs_Catalog_UpdateSymmetricalProductListItem',
					array('listId' => $list->getId(), 'productId' => $product->getId(), 'action' => 'remove'));
			}
		}
	}
}
