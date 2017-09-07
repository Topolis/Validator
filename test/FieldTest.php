<?php
/**
 * Created by PhpStorm.
 * User: bulla
 * Date: 08.06.17
 * Time: 13:55
 */

namespace Topolis\Validator\Schema;


class FieldTest extends \PHPUnit_Framework_TestCase {

    protected static $fieldMock = [
    ];

    public function testImportExport() {
        $field = new Field([
            "filter" => "SomeFilter",
            "options" => ["A1", "A2", "A3" => "Athree"],
            "default" => true,
            "required" => false,
            "something" => "bogus"
        ]);

        $this->assertInstanceOf("Topolis\Validator\Schema\Field", $field);

        $this->assertEquals([
            "filter" => "SomeFilter",
            "options" => ["A1", "A2", "A3" => "Athree"],
            "default" => true,
            "required" => false,
            "remove" => false,
            "strict" => false
        ], $field->export());
    }

    public function testGetFilter(){
        // Specified
        $field = new Field(["filter" => "SomeFilter"] + self::$fieldMock);
        $this->assertEquals("SomeFilter", $field->getFilter());

        // Nothing specified
        $field = new Field(self::$fieldMock);
        $this->assertEquals("Passthrough", $field->getFilter());
    }

    public function testGetOptions(){
        // Specified
        $options = ["two", 6, "C" => "D"];
        $field = new Field([ "options" => $options ] + self::$fieldMock);
        $this->assertEquals($options, $field->getOptions());

        // Nothing specified
        $field = new Field(self::$fieldMock);
        $this->assertEquals([], $field->getOptions());
    }

    public function testGetDefault(){
        // Specified
        $field = new Field([ "default" => "helloworld" ] + self::$fieldMock);
        $this->assertEquals("helloworld", $field->getDefault());

        // Nothing specified
        $field = new Field(self::$fieldMock);
        $this->assertNull($field->getDefault());
    }

    public function testGetRequired(){
        // Specified
        $field = new Field([ "required" => true ] + self::$fieldMock);
        $this->assertTrue($field->getRequired());

        $field = new Field([ "required" => false ] + self::$fieldMock);
        $this->assertFalse($field->getRequired());


        // Nothing specified
        $field = new Field(self::$fieldMock);
        $this->assertFalse($field->getRequired());
    }

    public function testGetRemove(){
        // Specified
        $field = new Field([ "remove" => true ] + self::$fieldMock);
        $this->assertTrue($field->getRemove());

        $field = new Field([ "remove" => false ] + self::$fieldMock);
        $this->assertFalse($field->getRemove());


        // Nothing specified
        $field = new Field(self::$fieldMock);
        $this->assertFalse($field->getRemove());
    }

    public function testMerge(){
        $fieldA = new Field([
            "filter" => "AFilter",
            "options" => ["A1", "A2", "A3" => "Athree"],
            "default" => true,
            "required" => true
        ] + self::$fieldMock);

        $fieldB = new Field([
            "filter" => "BFilter",
            "options" => ["B1", "B2", "B3" => "Bthree"],
            "required" => false
        ] + self::$fieldMock);

        $fieldA->merge($fieldB);

        $this->assertEquals([
            "filter" => "BFilter",
            "options" => ["A1", "A2", "A3" => "Athree", "B1", "B2", "B3" => "Bthree"],
            "default" => true,
            "required" => true,
            "remove" => false,
            "strict" => false
        ], $fieldA->export());
    }

}
