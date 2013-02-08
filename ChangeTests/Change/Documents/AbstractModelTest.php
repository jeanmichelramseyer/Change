<?php
namespace ChangeTests\Change\Documents;

use Change\Documents\AbstractModel;
use Change\Documents\ModelManager;

class AbstractModelTest extends \ChangeTests\Change\TestAssets\TestCase
{
	/**
	 * @param \Change\Application $application
	 */
	protected function compileDocuments(\Change\Application $application)
	{
		$compiler = new \Change\Documents\Generators\Compiler($application);
		$compiler->generate();
	}

	/**
	 * @return \Change\Documents\ModelManager
	 */
	public function testInitializeDB()
	{
		$application = $this->getApplication();
		$this->compileDocuments($application);
		return $this->getApplication()->getDocumentServices()->getModelManager();
	}

	/**
	 * @depends testInitializeDB
	 * @param \Change\Documents\ModelManager $modelManager
	 * @return \Change\Documents\ModelManager
	 */
	public function testGetInstance($modelManager)
	{
		$modelBasic = $modelManager->getModelByName('Project_Tests_Basic');
		$this->assertEquals('Project', $modelBasic->getVendorName());
		$this->assertEquals('Tests', $modelBasic->getShortModuleName());
		$this->assertEquals('Basic', $modelBasic->getShortName());
		$this->assertEquals('Project_Tests', $modelBasic->getModuleName());
		$this->assertEquals('Project_Tests_Basic', $modelBasic->getName());
		$this->assertFalse($modelBasic->isLocalized());
		$this->assertFalse($modelBasic->isFrontofficeIndexable());
		$this->assertFalse($modelBasic->isBackofficeIndexable());
		$this->assertFalse($modelBasic->isIndexable());
		$this->assertFalse($modelBasic->isEditable());
		$this->assertFalse($modelBasic->isPublishable());
		$this->assertFalse($modelBasic->useVersion());
		$this->assertFalse($modelBasic->useCorrection());
		$this->assertFalse($modelBasic->hasDescendants());
		$this->assertCount(0, $modelBasic->getDescendantsNames());
		$this->assertNull($modelBasic->getInjectedBy());
		$this->assertFalse($modelBasic->hasParent());
		$this->assertNull($modelBasic->getParentName());
		$this->assertCount(0, $modelBasic->getAncestorsNames());
		$this->assertEquals('Project_Tests_Basic', $modelBasic->getRootName());

		$this->assertCount(20, $modelBasic->getProperties());
		$this->assertArrayHasKey('pStr', $modelBasic->getProperties());

		$this->assertCount(0, $modelBasic->getLocalizedProperties());
		$this->assertCount(20, $modelBasic->getNonLocalizedProperties());
		$this->assertCount(0, $modelBasic->getPropertiesWithCorrection());
		$this->assertCount(0, $modelBasic->getLocalizedPropertiesWithCorrection());
		$this->assertCount(0, $modelBasic->getNonLocalizedPropertiesWithCorrection());

		$this->assertCount(0, $modelBasic->getIndexedProperties());
		$this->assertTrue($modelBasic->hasProperty('pStr'));
		$this->assertTrue($modelBasic->hasProperty('id'));
		$this->assertTrue($modelBasic->hasProperty('model'));

		$property = $modelBasic->getProperty('pStr');
		$this->assertInstanceOf('\Change\Documents\Property', $property);
		$this->assertEquals('pStr', $property->getName());

		$names = $modelBasic->getPropertiesNames();
		$this->assertCount(20, $names);
		$this->assertContains('id', $names);
		$this->assertContains('model', $names);
		$this->assertContains('creationDate', $names);
		$this->assertContains('modificationDate', $names);

		$this->assertFalse($modelBasic->hasCascadeDelete());

		$inverseProperties = $modelBasic->getInverseProperties();
		$this->assertArrayHasKey('ProjectTestsLocalizedPDocInst', $inverseProperties);
		$this->assertArrayHasKey('ProjectTestsLocalizedPDocArr', $inverseProperties);

		$this->assertTrue($modelBasic->hasInverseProperty('ProjectTestsLocalizedPDocArr'));

		$inverseProperty = $modelBasic->getInverseProperty('ProjectTestsLocalizedPDocArr');
		$this->assertInstanceOf('\Change\Documents\InverseProperty', $inverseProperty);
		$this->assertEquals('ProjectTestsLocalizedPDocArr', $inverseProperty->getName());
		$this->assertEquals('Project_Tests_Localized', $inverseProperty->getRelatedDocumentType());
		$this->assertEquals('pDocArr', $inverseProperty->getRelatedPropertyName());

		$this->assertEquals('test', $modelBasic->getIcon());
		$this->assertEquals('m.project.tests.document.basic.document-name', $modelBasic->getLabelKey());
		$this->assertEquals('Project_Tests_Basic', strval($modelBasic));
		return $modelManager;
	}

	/**
	 * @depends testGetInstance
	 * @param \Change\Documents\ModelManager $modelManager
	 * @return \Change\Documents\ModelManager
	 */
	public function testLocalized($modelManager)
	{
		$modelLocalized = $modelManager->getModelByName('Project_Tests_Localized');
		$this->assertTrue($modelLocalized->isLocalized());
		$this->assertCount(36, $modelLocalized->getProperties());
		$this->assertCount(17, $modelLocalized->getLocalizedProperties());
		$this->assertCount(19, $modelLocalized->getNonLocalizedProperties());

		$this->assertArrayHasKey('voLCID', $modelLocalized->getNonLocalizedProperties());
		$this->assertArrayHasKey('LCID', $modelLocalized->getLocalizedProperties());
		$this->assertArrayHasKey('creationDate', $modelLocalized->getLocalizedProperties());
		return $modelManager;
	}

	/**
	 * @depends testLocalized
	 * @param \Change\Documents\ModelManager $modelManager
	 * @return \Change\Documents\ModelManager
	 */
	public function testCorrection($modelManager)
	{
		$modelCorrection = $modelManager->getModelByName('Project_Tests_Correction');
		$this->assertTrue($modelCorrection->useCorrection());
		$this->assertCount(3, $modelCorrection->getPropertiesWithCorrection());
		$this->assertCount(2, $modelCorrection->getNonLocalizedPropertiesWithCorrection());
		$this->assertCount(1, $modelCorrection->getLocalizedPropertiesWithCorrection());
		return $modelManager;
	}

	/**
	 * @depends testCorrection
	 * @param \Change\Documents\ModelManager $modelManager
	 * @return \Change\Documents\ModelManager
	 */
	public function testExtend($modelManager)
	{
		$modelBase = $modelManager->getModelByName('Project_Tests_Correction');
		$modelExtend = $modelManager->getModelByName('Project_Tests_CorrectionExt');
		$this->assertEquals('Project_Tests_Correction', $modelBase->getRootName());
		$this->assertEquals('Project_Tests_Correction', $modelExtend->getRootName());
		$this->assertCount(1, $modelBase->getDescendantsNames());
		$this->assertContains('Project_Tests_CorrectionExt', $modelBase->getDescendantsNames());

		$this->assertEquals('Project_Tests_Correction', $modelExtend->getParentName());
		$this->assertCount(1, $modelExtend->getAncestorsNames());
		$this->assertContains('Project_Tests_Correction', $modelExtend->getAncestorsNames());

		$this->assertCount(5, $modelExtend->getPropertiesWithCorrection());
		$this->assertCount(3, $modelExtend->getNonLocalizedPropertiesWithCorrection());
		$this->assertCount(2, $modelExtend->getLocalizedPropertiesWithCorrection());

		$this->assertArrayHasKey('str2', $modelExtend->getPropertiesWithCorrection());
		$this->assertArrayHasKey('strext2', $modelExtend->getNonLocalizedPropertiesWithCorrection());
		$this->assertArrayHasKey('strext4', $modelExtend->getLocalizedPropertiesWithCorrection());
		return $modelManager;
	}
}