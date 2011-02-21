<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class ClassInfoTest extends SapphireTest {
	
	function testSubclassesFor() {
		$this->assertEquals(
			ClassInfo::subclassesFor('ClassInfoTest_BaseClass'),
			array(
				'ClassInfoTest_BaseClass' => 'ClassInfoTest_BaseClass',
				'ClassInfoTest_ChildClass' => 'ClassInfoTest_ChildClass',
				'ClassInfoTest_GrandChildClass' => 'ClassInfoTest_GrandChildClass'
			),
			'ClassInfo::subclassesFor() returns only direct subclasses and doesnt include base class'
		);
	}
	
	function testClassesForFolder() {
		//$baseFolder = Director::baseFolder() . '/' . SAPPHIRE_DIR . '/tests/_ClassInfoTest';
		//$manifestInfo = ManifestBuilder::get_manifest_info($baseFolder);
		
		$classes = ClassInfo::classes_for_folder('sapphire/tests');
		$this->assertContains(
			'classinfotest',
			$classes,
			'ClassInfo::classes_for_folder() returns classes matching the filename'
		);
		// $this->assertContains(
		// 			'ClassInfoTest_BaseClass',
		// 			$classes,
		// 			'ClassInfo::classes_for_folder() returns additional classes not matching the filename'
		// 		);
	}

	/**
	 * @covers ClassInfo::baseDataClass()
	 */
	public function testBaseDataClass() {
		$this->assertEquals('ClassInfoTest_BaseClass', ClassInfo::baseDataClass('ClassInfoTest_BaseClass'));
		$this->assertEquals('ClassInfoTest_BaseClass', ClassInfo::baseDataClass('ClassInfoTest_ChildClass'));
		$this->assertEquals('ClassInfoTest_BaseClass', ClassInfo::baseDataClass('ClassInfoTest_GrandChildClass'));

		$this->setExpectedException('Exception');
		ClassInfo::baseDataClass('DataObject');
	}

	/**
	 * @covers ClassInfo::ancestry()
	 */
	public function testAncestry() {
		$ancestry = ClassInfo::ancestry('SiteTree');
		$expect = ArrayLib::valuekey(array(
			'Object',
			'ViewableData',
			'DataObject',
			'SiteTree'
		));
		$this->assertEquals($expect, $ancestry);

		$ancestry = ClassInfo::ancestry('SiteTree', true);
		$this->assertEquals(array('SiteTree' => 'SiteTree'), $ancestry);

		$this->setExpectedException('Exception');
		ClassInfo::ancestry(42);
	}

	/**
	 * @covers ClassInfo::dataClassesFor()
	 */
	public function testDataClassesFor() {
		$expect = array(
			'ClassInfoTest_BaseDataClass' => 'ClassInfoTest_BaseDataClass',
			'ClassInfoTest_HasFields'     => 'ClassInfoTest_HasFields'
		);

		$classes = array(
			'ClassInfoTest_BaseDataClass',
			'ClassInfoTest_NoFields',
			'ClassInfoTest_HasFields'
		);

		foreach ($classes as $class) {
			$this->assertEquals($expect, ClassInfo::dataClassesFor($class));
		}
	}

}

class ClassInfoTest_BaseClass extends DataObject {
	
}

class ClassInfoTest_ChildClass extends ClassInfoTest_BaseClass {
	
}

class ClassInfoTest_GrandChildClass extends ClassInfoTest_ChildClass {
	
}

class ClassInfoTest_BaseDataClass extends DataObject {
	public static $db = array('Title' => 'Varchar');
}
class ClassInfoTest_NoFields extends ClassInfoTest_BaseDataClass {}
class ClassInfoTest_HasFields extends ClassInfoTest_NoFields {
	public static $db = array('Description' => 'Varchar');
}
