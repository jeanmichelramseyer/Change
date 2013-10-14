<?php
namespace Rbs\Website\Documents;

use Change\Documents\Events\Event;

/**
 * @name \Rbs\Website\Documents\SectionPageFunction
 */
class SectionPageFunction extends \Compilation\Rbs\Website\Documents\SectionPageFunction
{

	/**
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 */
	protected function attachEvents($eventManager)
	{
		$eventManager->attach(Event::EVENT_CREATE, array($this, 'validateUnique'), 1);
		$eventManager->attach(array(Event::EVENT_CREATED, Event::EVENT_UPDATED), array($this, 'hideLinksOnIndexPage'), 1);
	}


	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function validateUnique ($event)
	{
		if ($event->getParam('propertiesErrors') !== null)
		{
			return;
		}

		/* @var $document \Rbs\Website\Documents\SectionPageFunction */
		$document = $event->getDocument();
		$query = new \Change\Documents\Query\Query($document->getDocumentServices(), $document->getDocumentModel());
		$query->andPredicates($query->eq('section', $document->getSection()), $query->eq('functionCode', $document->getFunctionCode()));
		if ($query->getCountDocuments())
		{
			$event->setParam('propertiesErrors', array('functionCode' => array(new \Change\I18n\PreparedKey('m.rbs.website.documents.sectionpagefunction.error-not-unique', array(), array("code" => $document->getFunctionCode())))));
		}
	}

	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function hideLinksOnIndexPage($event)
	{
		$doc = $event->getDocument();
		if ($doc instanceof SectionPageFunction && $doc->getFunctionCode() == 'Rbs_Website_Section')
		{
			$page = $doc->getPage();
			if ($page instanceof \Rbs\Website\Documents\StaticPage && !$page->getHideLinks())
			{
				$page->setHideLinks(true);
				$page->update();
			}
		}
	}
}