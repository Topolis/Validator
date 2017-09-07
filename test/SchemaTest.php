<?php
/**
 * Created by PhpStorm.
 * User: bulla
 * Date: 08.06.17
 * Time: 11:44
 */

namespace Topolis\Validator\Schema;


use Topolis\Filter\Filter;

class SchemaTest extends \PHPUnit_Framework_TestCase {

    protected static $schemaMock = [
        "definitions" => [
            "one" => [],
            "two" => [
                "definitions" => []
            ]
        ],
        "conditionals" => [ [
            "condition" => "A == A",
            "definitions" => [
                "three" => []
            ]], [
            "condition" => "B == B",
            "definitions" => [
                "four" => []
            ]]
        ]
    ];

    public function testImport() {
        $schema = new Schema(self::$schemaMock);

        $this->assertInstanceOf("Topolis\Validator\Schema\Schema", $schema);
    }

    public function testGetType(){

        // Constants
        $this->assertNotEmpty(Schema::TYPE_SINGLE);
        $this->assertNotEmpty(Schema::TYPE_MULTIPLE);
        $this->assertNotEmpty(Schema::TYPE_DEFAULT);
        $this->assertNotEquals(Schema::TYPE_SINGLE,Schema::TYPE_MULTIPLE);

        // Specified "single"
        $schema = new Schema([ "type" => Schema::TYPE_SINGLE ] + self::$schemaMock);
        $this->assertEquals(Schema::TYPE_SINGLE, $schema->getType());

        // Specified "multiple"
        $schema = new Schema([ "type" => Schema::TYPE_MULTIPLE ] + self::$schemaMock);
        $this->assertEquals("multiple", $schema->getType());

        // Nothing specified
        $schema = new Schema(self::$schemaMock);
        $this->assertEquals(Schema::TYPE_DEFAULT, $schema->getType());
    }

    public function testGetIndex(){
        // Specified and multiple
        $options = ["one", 5, "A" => "B"];
        $filter = new Field([ "filter" => "SomeFilter", "options" => $options]);
        $schema = new Schema([ "filter" => "SomeFilter", "options" => $options, "type" => Schema::TYPE_MULTIPLE ] + self::$schemaMock);
        $this->assertEquals($filter->export(), $schema->getIndex()->export());

        // Specified and single
        $schema = new Schema([ "filter" => "SomeFilter", "options" => $options, "type" => Schema::TYPE_SINGLE ] + self::$schemaMock);
        $this->assertNull($schema->getIndex());

        // Nothing specified
        $schema = new Schema(self::$schemaMock);
        $this->assertNull($schema->getIndex());
    }

    public function testGetDefinitions(){
        $schema = new Schema(self::$schemaMock);

        $definitions = $schema->getDefinitions();

        $this->assertCount(2, $definitions);
        $this->assertInstanceOf("Topolis\Validator\Schema\Field", $definitions["one"]);
        $this->assertInstanceOf("Topolis\Validator\Schema\Schema", $definitions["two"]);
    }

    public function testGetConditionals(){
        $schema = new Schema(self::$schemaMock);

        $conditionals = $schema->getConditionals();

        $this->assertCount(2, $conditionals);
        $this->assertInstanceOf("Topolis\Validator\Schema\Conditional", $conditionals[0]);
        $this->assertInstanceOf("Topolis\Validator\Schema\Conditional", $conditionals[1]);
    }

    public function testGetDefault(){
        // Specified and single
        $schema = new Schema([ "default" => "Something" ] + self::$schemaMock);
        $this->assertEquals("Something", $schema->getDefault());

        // Nothing specified
        $schema = new Schema(self::$schemaMock);
        $this->assertNull($schema->getDefault());
    }

    public function testGetRequired(){
        // Specified and single
        $schema = new Schema([ "required" => true ] + self::$schemaMock);
        $this->assertTrue($schema->getRequired());

        // Nothing specified
        $schema = new Schema(self::$schemaMock);
        $this->assertFalse($schema->getRequired());
    }


    public function testMerge() {
        $schemaMockA = self::$schemaMock;

        $schemaMockB = [
            "default" => "someB",
            "required" => true,
            "type" => "multiple",
            "filter" => "somethingelse",
            "definitions" => [
                "one" => [
                    "definitions" => []
                ],
                "four" => [],
                "five" => [
                    "definitions" => []
                ]
            ],
            "conditionals" => [ [
                "condition" => "C == C",
                "definitions" => [
                    "five" => []
                ]]
            ]
        ];

        $schemaA = new Schema($schemaMockA);
        $schemaB = new Schema($schemaMockB);
        $schemaA->merge($schemaB);

        $this->assertEquals("multiple", $schemaA->getType());
        $this->assertEquals("somethingelse", $schemaA->getIndex()->getFilter());
        $this->assertEquals("someB", $schemaA->getDefault());
        $this->assertTrue($schemaA->getRequired());

        $definitions = $schemaA->getDefinitions();
        $this->assertCount(4, $definitions);
        $this->assertInstanceOf("Topolis\Validator\Schema\Schema", $definitions["one"]);
        $this->assertInstanceOf("Topolis\Validator\Schema\Schema", $definitions["two"]);
        $this->assertInstanceOf("Topolis\Validator\Schema\Field",  $definitions["four"]);
        $this->assertInstanceOf("Topolis\Validator\Schema\Schema", $definitions["five"]);

        $conditionals = $schemaA->getConditionals();
        $this->assertCount(3, $conditionals);
        $this->assertInstanceOf("Topolis\Validator\Schema\Conditional", $conditionals[0]);
        $this->assertInstanceOf("Topolis\Validator\Schema\Conditional", $conditionals[1]);
        $this->assertInstanceOf("Topolis\Validator\Schema\Conditional", $conditionals[2]);
    }

}
