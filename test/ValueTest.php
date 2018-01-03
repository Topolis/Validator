<?php
/**
 * Created by PhpStorm.
 * User: bulla
 * Date: 08.06.17
 * Time: 13:55
 */

namespace Topolis\Validator\Schema\Node;


use Topolis\Validator\Schema\NodeFactory;

class ValueTest extends \PHPUnit_Framework_TestCase {

    public function testImportExport() {
        $value = new Value([
            "filter" => "SomeFilter",
            "options" => ["A1", "A2", "A3" => "Athree"],
            "default" => true,
            "required" => false,
            "something" => "bogus"
        ], new NodeFactory());

        $this->assertInstanceOf("Topolis\Validator\Schema\Node\Value", $value);
        $this->assertInstanceOf("Topolis\Validator\Schema\INode", $value);

        $this->assertEquals([
            "filter" => "SomeFilter",
            "options" => ["A1", "A2", "A3" => "Athree"],
            "default" => true,
            "required" => false,
            "strict" => false
        ], $value->export());
    }

    public function testGetFilter(){
        // Specified
        $value = new Value(["filter" => "SomeFilter"], new NodeFactory());
        $this->assertEquals("SomeFilter", $value->getFilter());

        // Nothing specified
        $value = new Value([], new NodeFactory());
        $this->assertEquals("Passthrough", $value->getFilter());
    }

    public function testGetOptions(){
        // Specified
        $options = ["two", 6, "C" => "D"];
        $value = new Value([ "options" => $options ], new NodeFactory());
        $this->assertEquals($options, $value->getOptions());

        // Nothing specified
        $value = new Value([], new NodeFactory());
        $this->assertEquals([], $value->getOptions());
    }

    public function testGetDefault(){
        // Specified
        $value = new Value([ "default" => "helloworld" ], new NodeFactory());
        $this->assertEquals("helloworld", $value->getDefault());

        // Nothing specified
        $value = new Value([], new NodeFactory());
        $this->assertNull($value->getDefault());
    }

    public function testGetRequired(){
        // Specified
        $value = new Value([ "required" => true ], new NodeFactory());
        $this->assertTrue($value->getRequired());

        $value = new Value([ "required" => false ], new NodeFactory());
        $this->assertFalse($value->getRequired());


        // Nothing specified
        $value = new Value([], new NodeFactory());
        $this->assertFalse($value->getRequired());
    }

    public function testMerge(){
        $valueA = new Value([
            "filter" => "AFilter",
            "options" => ["A1", "A2", "A3" => "Athree"],
            "default" => true,
            "required" => true
        ], new NodeFactory());

        $valueB = new Value([
            "filter" => "BFilter",
            "options" => ["B1", "B2", "B3" => "Bthree"],
            "required" => false
        ], new NodeFactory());

        $valueA->merge($valueB);

        $this->assertEquals([
            "filter" => "BFilter",
            "options" => ["A1", "A2", "A3" => "Athree", "B1", "B2", "B3" => "Bthree"],
            "default" => true,
            "required" => true,
            "strict" => false
        ], $valueA->export());
    }

}
