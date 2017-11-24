<?php
/**
 * Created by PhpStorm.
 * User: bulla
 * Date: 08.06.17
 * Time: 13:55
 */

namespace Topolis\Validator;


use Topolis\Validator\Schema\Value;
use Topolis\Validator\Schema\Object;
use Topolis\Validator\Validators\FieldValidator;
use Topolis\Validator\Validators\SchemaValidator;

class SchemaValidatorTest extends \PHPUnit_Framework_TestCase {

    protected function assertValid($definition, $input, $expected, $data = []){
        $errorhandler = new StatusManager();
        $definition = new Object($definition);
        $validator = new SchemaValidator($definition, $errorhandler);

        $result = $validator->validate($input, $data);

        $message = "";
        if($errorhandler->getMessages())
            $message = $errorhandler->getMessages()[0]["message"]." - Value: '".(!is_array($errorhandler->getMessages()[0]["value"]) ? $errorhandler->getMessages()[0]["value"] : "array")."' path: ".implode(".",$errorhandler->getPath());

        $this->assertEmpty($errorhandler->getMessages(), $message);
        $this->assertTrue($errorhandler->getStatus(), $message);
        $this->assertEquals($expected, $result);
    }

    protected function assertInvalid($definition, $input, $status, $data = []){
        $errorhandler = new StatusManager();
        $definition = new Object($definition);
        $validator = new SchemaValidator($definition, $errorhandler);

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
            "definitions" => [
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
        // Invalid test - wrong keys
        $this->assertInvalid(
            $schema,
            ["one" => "AAA", "two" => "BBB", "three" => "CCC"],
            StatusManager::INVALID
        );

        // Invalid test - missing required
        $this->assertInvalid(
            $schema,
            ["one" => "AXA"],
            StatusManager::INVALID
        );
    }

    public function testValidateFilterFieldsRemove(){

        $fieldA = [
            "filter" => "Test",
            "strict" => true,
            "options" => ["expected" => "AAA"],
            "remove" => false
        ];
        $fieldB = [
            "filter" => "Test",
            "strict" => true,
            "options" => ["expected" => "AAA"],
            "remove" => ["BBB","AAA"]
        ];
        $fieldC = [
            "filter" => "Test",
            "strict" => true,
            "options" => ["expected" => "AAA"],
            "remove" => ["CCC","DDD"]
        ];

        // Valid test w/o remove
        $this->assertValid(
            ["definitions" => ["one" => $fieldA]],
            ["one" => "AAA"],
            ["one" => "AAA"]
        );

        // Valid test with matching remove
        $this->assertValid(
            ["definitions" => ["one" => $fieldB]],
            ["one" => "AAA"],
            []
        );

        // Valid test with non-matching remove
        $this->assertValid(
            ["definitions" => ["one" => $fieldC]],
            ["one" => "AAA"],
            ["one" => "AAA"]
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
            "definitions" => [
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
            "definitions" => [
                "one" => $fieldA,
                "two" => $fieldB
            ]
        ];
        $schema = [
            "definitions" => [
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

        // invalid additional key
        $this->assertInvalid(
            $schema,
            ["max" => ["one" => "A1A", "two" => "B1B"], "paula" => ["one" => "A2A", "two" => "B2B"]],
            StatusManager::INVALID
        );

    }

}
