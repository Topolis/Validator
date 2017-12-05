<?php
/**
 * Created by PhpStorm.
 * User: bulla
 * Date: 08.06.17
 * Time: 11:44
 */

namespace Topolis\Validator\Schema\Node;

use Topolis\Validator\Schema\NodeFactory;

class ObjectTest extends \PHPUnit_Framework_TestCase {

    /* @var NodeFactory $factory */
    protected $factory;
    
    protected function setUp() {
        $this->factory = new NodeFactory();
        $this->factory->registerClass(Listing::class);
        $this->factory->registerClass(Object::class);
        $this->factory->registerClass(Value::class);
    }

    protected static $schemaMock = [
        "properties" => [
            "one" => ["filter" => "Passthrough"],
            "two" => [
                "properties" => []
            ]
        ],
        "conditionals" => [ [
            "condition" => "A == A",
            "properties" => [
                "three" => ["filter" => "Passthrough"]
            ]], [
            "condition" => "B == B",
            "properties" => [
                "four" => ["filter" => "Passthrough"]
            ]]
        ]
    ];

    public function testImport() {
        $object = new Object(self::$schemaMock, $this->factory);

        $this->assertInstanceOf("Topolis\Validator\Schema\Node\Object", $object);
        $this->assertInstanceOf("Topolis\Validator\Schema\INode", $object);
    }

    public function testGetType(){

        // Constants
        $this->assertNotEmpty(Object::TYPE_SINGLE);
        $this->assertNotEmpty(Object::TYPE_MULTIPLE);
        $this->assertNotEmpty(Object::TYPE_DEFAULT);
        $this->assertNotEquals(Object::TYPE_SINGLE,Object::TYPE_MULTIPLE);

        // Specified "single"
        $object = new Object([ "type" => Object::TYPE_SINGLE ] + self::$schemaMock, $this->factory);
        $this->assertEquals(Object::TYPE_SINGLE, $object->getType());

        // Specified "multiple"
        $object = new Object([ "type" => Object::TYPE_MULTIPLE ] + self::$schemaMock, $this->factory);
        $this->assertEquals("multiple", $object->getType());

        // Nothing specified
        $object = new Object(self::$schemaMock, $this->factory);
        $this->assertEquals(Object::TYPE_DEFAULT, $object->getType());
    }

    public function testGetIndex(){
        // Specified and multiple
        $options = ["one", 5, "A" => "B"];
        $filter = new Value([ "filter" => "SomeFilter", "options" => $options], $this->factory);
        $object = new Object([ "filter" => "SomeFilter", "options" => $options, "type" => Object::TYPE_MULTIPLE ] + self::$schemaMock, $this->factory);
        $this->assertEquals($filter->export(), $object->getIndex()->export());

        // Specified and single
        $object = new Object([ "filter" => "SomeFilter", "options" => $options, "type" => Object::TYPE_SINGLE ] + self::$schemaMock, $this->factory);
        $this->assertNull($object->getIndex());

        // Nothing specified
        $object = new Object(self::$schemaMock, $this->factory);
        $this->assertNull($object->getIndex());
    }

    public function testGetProperties(){
        $object = new Object(self::$schemaMock, $this->factory);

        $properties = $object->getProperties();

        $this->assertCount(2, $properties);
        $this->assertInstanceOf("Topolis\Validator\Schema\Node\Value", $properties["one"]);
        $this->assertInstanceOf("Topolis\Validator\Schema\Node\Object", $properties["two"]);
    }

    public function testGetConditionals(){
        $object = new Object(self::$schemaMock, $this->factory);

        $conditionals = $object->getConditionals();

        $this->assertCount(2, $conditionals);
        $this->assertInstanceOf("Topolis\Validator\Schema\Conditional", $conditionals[0]);
        $this->assertInstanceOf("Topolis\Validator\Schema\Conditional", $conditionals[1]);
    }

    public function testGetDefault(){
        // Specified and single
        $object = new Object([ "default" => "Something" ] + self::$schemaMock, $this->factory);
        $this->assertEquals("Something", $object->getDefault());

        // Nothing specified
        $object = new Object(self::$schemaMock, $this->factory);
        $this->assertNull($object->getDefault());
    }

    public function testGetRequired(){
        // Specified and single
        $object = new Object([ "required" => true ] + self::$schemaMock, $this->factory);
        $this->assertTrue($object->getRequired());

        // Nothing specified
        $object = new Object(self::$schemaMock, $this->factory);
        $this->assertFalse($object->getRequired());
    }


    public function testMerge() {
        $schemaMockA = self::$schemaMock;

        $schemaMockB = [
            "default" => "someB",
            "required" => true,
            "type" => "multiple",
            "filter" => "somethingelse",
            "properties" => [
                "one" => [
                    "properties" => []
                ],
                "four" => ["filter" => "Passthrough"],
                "five" => [
                    "properties" => []
                ]
            ],
            "conditionals" => [ [
                "condition" => "C == C",
                "properties" => [
                    "five" => ["filter" => "Passthrough"]
                ]]
            ]
        ];

        $schemaA = new Object($schemaMockA, $this->factory);
        $schemaB = new Object($schemaMockB, $this->factory);
        $schemaA->merge($schemaB);

        $this->assertEquals("multiple", $schemaA->getType());
        $this->assertEquals("somethingelse", $schemaA->getIndex()->getFilter());
        $this->assertEquals("someB", $schemaA->getDefault());
        $this->assertTrue($schemaA->getRequired());

        $definitions = $schemaA->getProperties();
        $this->assertCount(4, $definitions);
        $this->assertInstanceOf("Topolis\Validator\Schema\Node\Object", $definitions["one"]);
        $this->assertInstanceOf("Topolis\Validator\Schema\Node\Object", $definitions["two"]);
        $this->assertInstanceOf("Topolis\Validator\Schema\Node\Value",  $definitions["four"]);
        $this->assertInstanceOf("Topolis\Validator\Schema\Node\Object", $definitions["five"]);

        $conditionals = $schemaA->getConditionals();
        $this->assertCount(3, $conditionals);
        $this->assertInstanceOf("Topolis\Validator\Schema\Conditional", $conditionals[0]);
        $this->assertInstanceOf("Topolis\Validator\Schema\Conditional", $conditionals[1]);
        $this->assertInstanceOf("Topolis\Validator\Schema\Conditional", $conditionals[2]);
    }

}
