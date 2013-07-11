<?php
namespace Rbs\Catalog\Http\Rest\Actions;

use \Change\Documents\Query\Builder;
use Zend\Http\Response as HttpResponse;
use Change\Http\Rest\Result\DocumentLink;

/**
 * @name \Rbs\Catalog\Http\Rest\Actions\SetProductCategories
 */
class SetProductCategories
{
	/**
	 * Use Event Params: tags[], docId
	 * @param \Change\Http\Event $event
	 * @throws \Exception
	 */
	public function execute($event)
	{
		$transactionManager = $event->getApplicationServices()->getTransactionManager();
		try
		{
			$transactionManager->begin();

			$dm = $event->getDocumentServices()->getDocumentManager();
			$productId = $event->getParam('productId');
			$product = $dm->getDocumentInstance($productId);
			if (!($product instanceof \Rbs\Catalog\Documents\AbstractProduct))
			{
				throw new \RuntimeException('Invalid product id.', 999999);
			}
			$conditionId = $event->getParam('conditionId');

			$categoryIds = $event->getParam('categoryIds');
			$priorities = $event->getParam('priorities');
			$product->setProductIds($conditionId, $categoryIds, $priorities);

			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
		}

		$get = new GetProductCategories();
		$get->execute($event);
	}
}