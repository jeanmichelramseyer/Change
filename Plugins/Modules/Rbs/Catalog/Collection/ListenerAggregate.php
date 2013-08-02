<?php
namespace Rbs\Catalog\Collection;

use Zend\EventManager\EventManagerInterface;

/**
 * @name \Rbs\Catalog\Collection\ListenerAggregate
 */
class ListenerAggregate implements \Zend\EventManager\ListenerAggregateInterface
{
	/**
	 * Attach one or more listeners
	 * Implementors may add an optional $priority argument; the EventManager
	 * implementation will pass this to the aggregate.
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function attach(EventManagerInterface $events)
	{
		$callback = function (\Zend\EventManager\Event $event)
		{
			switch ($event->getParam('code'))
			{
				case 'Rbs_Catalog_Collection_ProductSortOrders':
					(new Collections())->addProductSortOrders($event);
					break;
				case 'Rbs_Catalog_Collection_AttributeValueTypes':
					(new Collections())->addAttributeValueTypes($event);
					break;
				case 'Rbs_Catalog_Collection_AttributeCollections':
					(new Collections())->addAttributeCollections($event);
					break;
				case 'Rbs_Catalog_Collection_AttributeSet':
					(new Collections())->addAttributeSet($event);
					break;
			}
		};
		$events->attach('getCollection', $callback, 10);

		$callback = function (\Zend\EventManager\Event $event)
		{
			$codes = $event->getParam('codes', array());
			$codes[] = 'Rbs_Catalog_Collection_ProductSortOrders';
			$codes[] = 'Rbs_Catalog_Collection_AttributeValueTypes';
			$codes[] = 'Rbs_Catalog_Collection_AttributeCollections';
			$codes[] = 'Rbs_Catalog_Collection_AttributeSet';
			$event->setParam('codes', $codes);
		};
		$events->attach('getCodes', $callback, 1);
	}

	/**
	 * Detach all previously attached listeners
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function detach(EventManagerInterface $events)
	{
		// TODO: Implement detach() method.
	}
}