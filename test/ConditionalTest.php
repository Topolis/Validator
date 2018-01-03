<?php
/**
 * Created by PhpStorm.
 * User: bulla
 * Date: 08.06.17
 * Time: 13:55
 */

namespace Topolis\Validator\Schema;

use Topolis\Validator\Schema\Node\Listing;
use Topolis\Validator\Schema\Node\Object;
use Topolis\Validator\Schema\Node\Value;

class ConditionalTest extends \PHPUnit_Framework_TestCase {

    /* @var NodeFactory $factory */
    protected $factory;

    protected function setUp() {
        $this->factory = new NodeFactory();
        $this->factory->registerClass(Listing::class);
        $this->factory->registerClass(Object::class);
        $this->factory->registerClass(Value::class);
    }

    public function testImportExport(){
        $conditional = new Conditional([
            "condition" => "some.thing > 5",
            "filter" => "filterA"
        ], ["filter" => "filterB", "options" => ["A" => "B"]], $this->factory);

        $this->assertInstanceOf("Topolis\Validator\Schema\Conditional", $conditional);
        $this->assertInstanceOf("Topolis\Validator\Schema\INode", $conditional->getNode());

        $this->assertEquals([
            "condition" => "some.thing > 5",
            'mode' => 'merge',
            "filter" => "filterA",
            'options' => ["A" => "B"],
            "default" => null,
            "required" => false,
            'strict' => false
        ], $conditional->export());
    }

    /*
    public function testSetParser(){
        $this->markTestIncomplete('This test has not been implemented yet as the method is not used atm.');
    }
    */

    public function testGetDefinitionMerge(){

        $conditional = new Conditional([
            "condition" => "some.thing <= 9",
            "filter" => "Afilter",
            "options" => ["A1", "A2", "A3" => "Athree"],
            "required" => true
        ], [
            "filter" => "Bfilter",
            "options" => ["B1" => "B"],
            "strict" => true
        ], $this->factory );

        $this->assertInstanceOf("Topolis\Validator\Schema\Conditional", $conditional);
        $this->assertInstanceOf("Topolis\Validator\Schema\Node\Value", $conditional->getNode());

        $this->assertEquals([
            "condition" => "some.thing <= 9",
            "mode" => "merge",
            "filter" => "Afilter",
            'options' => ["A1", "A2", "A3" => "Athree", "B1" => "B"],
            "default" => null,
            "required" => true,
            'strict' => true
        ], $conditional->export());
    }

    public function testGetDefinitionReplace(){

        $conditional = new Conditional([
            "condition" => "some.thing <= 9",
            "mode" => "replace",
            "filter" => "Afilter",
            "options" => ["A1", "A2", "A3" => "Athree"],
            "required" => true
        ], [
            "filter" => "Bfilter",
            "options" => ["B1" => "B"],
            "strict" => true
        ], $this->factory );

        $this->assertInstanceOf("Topolis\Validator\Schema\Conditional", $conditional);
        $this->assertInstanceOf("Topolis\Validator\Schema\Node\Value", $conditional->getNode());

        $this->assertEquals([
            "condition" => "some.thing <= 9",
            "mode" => "replace",
            "filter" => "Afilter",
            'options' => ["A1", "A2", "A3" => "Athree"],
            "default" => null,
            "required" => true,
            'strict' => false
        ], $conditional->export());
    }

    /* Not needed - This method only calls the conditionalParser which has it's own tests
    public function testEvaluate(){
    }
    */
}
