<?php

namespace ChangeTests\Website\Blocks;

use Change\Presentation\Blocks\BlockManager;
use Change\Presentation\Blocks\Event;
use Change\Presentation\Layout\Block;

class ThreadTest extends \ChangeTests\Change\TestAssets\TestCase
{
	const CURRENT_WEBSITE_ID = 456;

	protected function getTestParameterizeEvent()
	{
		$event = new Event(BlockManager::EVENT_PARAMETERIZE);
		$event->setParams($this->getDefaultEventArguments());
		$layout = new Block();
		$layout->initialize(array('id' => 1, 'name' => 'Rbs_Website_Thread'));
		$event->setBlockLayout($layout);
		return $event;
	}

	public static function setUpBeforeClass()
	{
		static::initDocumentsClasses();
	}

	public function testOnParameterize()
	{
		$event = $this->getTestParameterizeEvent();
		$layout = $event->getBlockLayout();
		$layout->setParameters(array('separator' => '>'));

		/* @var $website \Rbs\Website\Documents\Website */
		$website = $this->getApplicationServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Website_Website');
		$website->initialize(self::CURRENT_WEBSITE_ID);

		/* @var $section \Rbs\Website\Documents\Topic */
		$pathRule = new \Change\Http\Web\PathRule($website, 'test/toto/titi/tata.html');
		$section = $this->getApplicationServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Website_Topic');
		$section->setWebsite($website);
		$section->initialize(789);

		/* @var $page \Rbs\Website\Documents\StaticPage */
		$page = $this->getApplicationServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Website_StaticPage');
		$page->setSection($section);
		$page->initialize(123);
		$event->setParam('page', $page);

		$pathRule->setSectionId(789);
		$event->setParam('pathRule', $pathRule);
		$block = new \Rbs\Website\Blocks\Thread();
		$block->onParameterize($event);
		$parameters = $event->getBlockParameters();

		$this->assertInstanceOf('\\Change\\Presentation\\Blocks\\Parameters', $parameters);

		$this->assertTrue(isset($parameters->templateName));
		$this->assertFalse(isset($parameters->zearazefazefazfazf));

		$meta = $parameters->getParameterMeta('separator');
		$this->assertInstanceOf('\Change\Presentation\Blocks\ParameterMeta', $meta);
		$this->assertEquals('/', $meta->getDefaultValue());
		$this->assertEquals('>', $parameters->getParameter('separator'));

		$meta = $parameters->getParameterMeta('templateName');
		$this->assertInstanceOf('\Change\Presentation\Blocks\ParameterMeta', $meta);
		$this->assertEquals('thread.twig', $meta->getDefaultValue());
		$this->assertEquals('thread.twig', $parameters->getParameter('templateName'));

		$meta = $parameters->getParameterMeta('documentId');
		$this->assertInstanceOf('\Change\Presentation\Blocks\ParameterMeta', $meta);
		$this->assertNull($meta->getDefaultValue());
		$this->assertEquals(123, $parameters->getParameter('documentId'));

		$meta = $parameters->getParameterMeta('sectionId');
		$this->assertInstanceOf('\Change\Presentation\Blocks\ParameterMeta', $meta);
		$this->assertNull($meta->getDefaultValue());
		$this->assertEquals(789, $parameters->getParameter('sectionId'));
		return $event;
	}
}
