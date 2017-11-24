<?php
/**
 * Created by PhpStorm.
 * User: bulla
 * Date: 08.06.17
 * Time: 13:55
 */

namespace Topolis\Validator\Schema;

class ConditionalTest extends \PHPUnit_Framework_TestCase {

    public function testImportExport(){
        $conditional = new Conditional([
            "condition" => "some.thing > 5"
        ], "Topolis\Validator\Schema\Schema");

        $this->assertInstanceOf("Topolis\Validator\Schema\Conditional", $conditional);
        $this->assertInstanceOf("Topolis\Validator\Schema\Schema", $conditional->getDefinition());

        $this->assertEquals([
            "condition" => "some.thing > 5",
            "definitions" => [],
            "type" => "single",
            "conditionals" => [],
            "default" => null,
            "required" => false
        ], $conditional->export());
    }

    /*
    public function testSetParser(){
        $this->markTestIncomplete('This test has not been implemented yet as the method is not used atm.');
    }
    */

    public function testGetDefinition(){

        $definition = new Object([
            "filter" => "AFilter",
            "options" => ["A1", "A2", "A3" => "Athree"],
            "required" => true
        ]);

        $conditional = new Conditional([
            "condition" => "some.thing <= 9",
        ] + $definition->export(), "Topolis\Validator\Schema\Schema" );

        $this->assertInstanceOf("Topolis\Validator\Schema\Conditional", $conditional);
        $this->assertInstanceOf("Topolis\Validator\Schema\Schema", $conditional->getDefinition());

        $this->assertEquals([
            "condition" => "some.thing <= 9",
            "definitions" => [],
            "type" => "single",
            "conditionals" => [],
            "default" => null,
            "required" => true
        ], $conditional->export());
    }

    /* Not needed - This method only calls the conditionalParser which has it's own tests
    public function testEvaluate(){
    }
    */
}
