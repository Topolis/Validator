<?php
/**
 * Created by PhpStorm.
 * User: bulla
 * Date: 08.06.17
 * Time: 13:55
 */

namespace Topolis\Validator\Schema\Validators;


use Topolis\Validator\Schema\Node\Listing;
use Topolis\Validator\Schema\NodeFactory;
use Topolis\Validator\Schema\Node\Value;
use Topolis\Validator\Schema\Node\Object;
use Topolis\Validator\StatusManager;

class ObjectValidatorTest extends \PHPUnit_Framework_TestCase {

    /* @var NodeFactory $factory */
    protected $factory;

    protected function setUp() {
        $this->factory = new NodeFactory();
        $this->factory->registerClass(Listing::class);
        $this->factory->registerClass(Object::class);
        $this->factory->registerClass(Value::class);
    }

    protected function assertValid($definition, $input, $expected, $data = []){
        $errorhandler = new StatusManager();
        $definition = new Object($definition, $this->factory);
        $validator = new ObjectValidator($definition, $errorhandler, $this->factory);

        $result = $validator->validate($input, $data);

        $message = "";
        if($errorhandler->getMessages())
            $message = $errorhandler->getMessages()[0]["message"]." - Value: '".(!is_array($errorhandler->getMessages()[0]["value"]) ? $errorhandler->getMessages()[0]["value"] : "array")."' path: ".implode(".",$errorhandler->getPath());

        $this->assertEmpty($errorhandler->getMessages(), $message);
        $this->assertEquals(1, $errorhandler->getStatus(), $message);
        $this->assertEquals($expected, $result);
    }

    protected function assertInvalid($definition, $input, $status, $data = []){
        $errorhandler = new StatusManager();
        $definition = new Object($definition, $this->factory);
        $validator = new ObjectValidator($definition, $errorhandler, $this->factory);

        $result = $validator->validate($input, $data);

        $message = "";
        if($errorhandler->getMessages())
            $message = $errorhandler->getMessages()[0]["message"]." - Value: '".(!is_array($errorhandler->getMessages()[0]["value"]) ? $errorhandler->getMessages()[0]["value"] : "array")."' path: ".implode(".",$errorhandler->getPath());

        $this->assertNotEmpty($errorhandler->getMessages(), $message);
        $this->assertSame($status, $errorhandler->getStatus(), $message);
        $this->assertNotEquals($input, $result);
    }

    public function testValidateFilterFields(){

        $fieldA = [
            "filter" => "Test",
            "strict" => true,
            "options" => ["expected" => "AAA"],
            "default" => "AAA"
        ];
        $fieldB = [
            "filter" => "Test",
            "strict" => true,
            "options" => ["expected" => "BBB"],
            "required" => true
        ];
        $schema = [
            "properties" => [
                "one" => $fieldA,
                "two" => $fieldB
            ]
        ];

        // Valid test
        $this->assertValid(
            $schema,
            ["one" => "AAA", "two" => "BBB"],
            ["one" => "AAA", "two" => "BBB"]
        );

        // Valid test with default
        $this->assertValid(
            $schema,
            ["two" => "BBB"],
            ["one" => "AAA", "two" => "BBB"]
        );

        // Invalid test - wrong value
        $this->assertInvalid(
            $schema,
            ["one" => "AAA", "two" => "BXB"],
            StatusManager::INVALID
        );
        // Invalid test - surplus keys
        $this->assertInvalid(
            $schema,
            ["one" => "AAA", "two" => "BBB", "three" => "CCC"],
            StatusManager::SANITIZED
        );

        // Invalid test - missing required
        $this->assertInvalid(
            $schema,
            ["one" => "AXA"],
            StatusManager::INVALID
        );
    }

    public function testValidateArrayMultiple(){
        $fieldA = [
            "filter" => "Test",
            "strict" => true,
            "options" => ["expected" => ["A1A","A2A"]],
        ];
        $fieldB = [
            "filter" => "Test",
            "strict" => true,
            "options" => ["expected" => ["B1B","B2B"]],
        ];
        $schema = [
            "type" => "multiple",
            "filter" => "Test",
            "options" => ["expected" => ["max","sam"]],
            "properties" => [
                "one" => $fieldA,
                "two" => $fieldB
            ]
        ];

        // simple
        $this->assertValid(
            $schema,
            ["max" => ["one" => "A1A", "two" => "B1B"], "sam" => ["one" => "A2A", "two" => "B2B"]],
            ["max" => ["one" => "A1A", "two" => "B1B"], "sam" => ["one" => "A2A", "two" => "B2B"]]
        );
        $this->assertValid(
            $schema,
            ["sam" => ["one" => "A2A", "two" => "B2B"]],
            ["sam" => ["one" => "A2A", "two" => "B2B"]]
        );
        $this->assertInvalid(
            $schema,
            ["max" => ["one" => "wrong", "two" => "B1B"], "sam" => ["one" => "A2A", "two" => "B2B"]],
            StatusManager::INVALID
        );

        // invalid keys
        $this->assertInvalid(
            $schema,
            ["max" => ["one" => "A1A", "two" => "B1B"], "paula" => ["one" => "A2A", "two" => "B2B"]],
            StatusManager::INVALID
        );

    }

    public function testValidateArraySingle(){
        $fieldA = [
            "filter" => "Test",
            "strict" => true,
            "options" => ["expected" => ["A1A","A2A"]],
        ];
        $fieldB = [
            "filter" => "Test",
            "strict" => true,
            "options" => ["expected" => ["B1B","B2B"]],
        ];
        $subschema = [
            "properties" => [
                "one" => $fieldA,
                "two" => $fieldB
            ]
        ];
        $schema = [
            "properties" => [
                "sam" => $subschema,
                "max" => $subschema
            ]
        ];

        // simple
        $this->assertValid(
            $schema,
            ["max" => ["one" => "A1A", "two" => "B1B"], "sam" => ["one" => "A2A", "two" => "B2B"]],
            ["max" => ["one" => "A1A", "two" => "B1B"], "sam" => ["one" => "A2A", "two" => "B2B"]]
        );

        $this->assertInvalid(
            $schema,
            ["max" => ["one" => "wrong", "two" => "B1B"], "sam" => ["one" => "A2A", "two" => "B2B"]],
            StatusManager::INVALID
        );



    }

    public function testValidateArraySingleOffkeys(){
        $fieldA = [
            "filter" => "Test",
            "strict" => true,
            "options" => ["expected" => ["A1A","A2A"]],
        ];
        $fieldB = [
            "filter" => "Test",
            "strict" => true,
            "options" => ["expected" => ["B1B","B2B"]],
        ];
        $subschema = [
            "properties" => [
                "one" => $fieldA,
                "two" => $fieldB
            ]
        ];
        $schema = [
            "properties" => [
                "sam" => $subschema,
                "max" => $subschema
            ]
        ];

        // invalid additional key
        $this->assertInvalid(
            $schema,
            ["max" => ["one" => "A1A", "two" => "B1B"], "paula" => ["one" => "A2A", "two" => "B2B"]],
            StatusManager::SANITIZED
        );

        $this->assertValid(
            $schema,
            ["sam" => ["one" => "A1A", "two" => "B2B"]],
            ["sam" => ["one" => "A1A", "two" => "B2B"]]
        );

        $this->assertValid(
            $schema,
            ["sam" => ["two" => "B2B"]],
            ["sam" => ["two" => "B2B"]]
        );

    }

}
