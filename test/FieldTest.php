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
        $field = new Value([
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
        $field = new Value(["filter" => "SomeFilter"] + self::$fieldMock);
        $this->assertEquals("SomeFilter", $field->getFilter());

        // Nothing specified
        $field = new Value(self::$fieldMock);
        $this->assertEquals("Passthrough", $field->getFilter());
    }

    public function testGetOptions(){
        // Specified
        $options = ["two", 6, "C" => "D"];
        $field = new Value([ "options" => $options ] + self::$fieldMock);
        $this->assertEquals($options, $field->getOptions());

        // Nothing specified
        $field = new Value(self::$fieldMock);
        $this->assertEquals([], $field->getOptions());
    }

    public function testGetDefault(){
        // Specified
        $field = new Value([ "default" => "helloworld" ] + self::$fieldMock);
        $this->assertEquals("helloworld", $field->getDefault());

        // Nothing specified
        $field = new Value(self::$fieldMock);
        $this->assertNull($field->getDefault());
    }

    public function testGetRequired(){
        // Specified
        $field = new Value([ "required" => true ] + self::$fieldMock);
        $this->assertTrue($field->getRequired());

        $field = new Value([ "required" => false ] + self::$fieldMock);
        $this->assertFalse($field->getRequired());


        // Nothing specified
        $field = new Value(self::$fieldMock);
        $this->assertFalse($field->getRequired());
    }

    public function testGetRemove(){
        // Specified
        $field = new Value([ "remove" => true ] + self::$fieldMock);
        $this->assertTrue($field->getRemove());

        $field = new Value([ "remove" => false ] + self::$fieldMock);
        $this->assertFalse($field->getRemove());


        // Nothing specified
        $field = new Value(self::$fieldMock);
        $this->assertFalse($field->getRemove());
    }

    public function testMerge(){
        $fieldA = new Value([
            "filter" => "AFilter",
            "options" => ["A1", "A2", "A3" => "Athree"],
            "default" => true,
            "required" => true
        ] + self::$fieldMock);

        $fieldB = new Value([
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
