<?php
namespace ChangeTests\Documents\Generators;

/**
 * @name \ChangeTests\Documents\Generators\ModelTest
 */
class ModelTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$model = new \Change\Documents\Generators\Model('generic', 'document');
		$this->assertEquals('generic', $model->getModuleName());
		$this->assertEquals('document', $model->getDocumentName());
		return $model;
	}
	
	/**
	 * @depends testConstruct
	 */	
	public function testSetXmlDocument(\Change\Documents\Generators\Model $model)
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->load(__DIR__ . '/TestAssets/document.xml');
		
		$model->setXmlDocument($doc);
		$this->assertEquals('4.0', $model->getModelVersion());
		$this->assertEquals('document', $model->getIcon());
		$this->assertTrue($model->getHasUrl());
		$this->assertTrue($model->getUsePublicationDates());
		$this->assertTrue($model->getBackofficeIndexable());
		$this->assertFalse($model->getIndexable());
		$this->assertFalse($model->getUseCorrection());
		$this->assertNull($model->getExtend());
		$this->assertFalse($model->getLocalized());
		$this->assertFalse($model->getInject());
		
		$this->assertCount(13, $model->getProperties());
		return $model;
	}
	
	/**
	 * @depends testSetXmlDocument
	 */
	public function testApplyDefault(\Change\Documents\Generators\Model $defaultmodel)
	{
		
		$model2 = new \Change\Documents\Generators\Model('website', 'page');
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->load(__DIR__ . '/TestAssets/page.xml');
		$model2->setXmlDocument($doc);

		$this->assertCount(18, $model2->getProperties());
		$model2->applyDefault($defaultmodel);
		$this->assertCount(28, $model2->getProperties());	
	}
}