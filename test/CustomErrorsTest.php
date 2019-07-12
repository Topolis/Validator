<?php
/**
 * Created by PhpStorm.
 * User: bulla
 * Date: 08.06.17
 * Time: 13:55
 */

namespace Topolis\Validator\Schema\Validators;


use Topolis\Validator\Schema\Node\Listing;
use Topolis\Validator\Schema\Node\Properties;
use Topolis\Validator\Schema\Node\Value;
use Topolis\Validator\Schema\NodeFactory;
use Topolis\Validator\StatusManager;
use Topolis\Validator\Validator;

class CustomErrorsTest extends \PHPUnit_Framework_TestCase {

    /* @var NodeFactory $factory */
    protected $factory;

    protected function setUp() {
        $this->factory = new NodeFactory();
        $this->factory->registerClass(Listing::class);
        $this->factory->registerClass(Properties::class);
        $this->factory->registerClass(Value::class);
    }

    protected function assertCustomError($definition, $input, $code, $message){

        $errorhandler = new StatusManager();
        $schema = $this->factory->createNode($definition);
        $validator = $this->factory->createValidator($schema, $errorhandler);

        $validator->validate($input);

        self::assertEquals($code, $errorhandler->getStatus());
        self::assertEquals($message, $errorhandler->getMessages()[0]['message'] ?? null);
    }

    public function testValue(){

        $schema = [
            "filter" => "Test",
            "strict" => true,
            "options" => ["expected" => "test-value"],
            "errors" => [-11 => [
                "code" => -768,
                "message" => "A custom error message!"
            ]]
        ];

        // Condition B applies and is invalid
        $this->assertCustomError(
            $schema,
            "wrong-value",
            -768,
            "A custom error message!"
        );
    }

    public function testProperties(){

        $fieldA = [
            "filter" => "Test",
            "options" => ["expected" => "AAA"],
            "errors" => [-11 => [
                "code" => -300,
                "message" => "AAA custom error"
            ]]
        ];
        $fieldB = [
            "filter" => "Test",
            "options" => ["expected" => "BBB"],
            "errors" => [-11 => [
                "code" => -400,
                "message" => "BBB custom error"
            ]]
        ];
        $schema = [
            "properties" => [
                "one" => $fieldA,
                "two" => $fieldB
            ]
        ];

        $this->assertCustomError(
            $schema, [
                "one" => "ZZZ",
                "two" => "BBB"
            ],
            -300,
            "AAA custom error"
        );

        $this->assertCustomError(
            $schema, [
                "one" => "AAA",
                "two" => "ZZZ"
            ],
            -400,
            "BBB custom error"
        );

    }

    public function testPropertiesMissing(){

        $schema = [
            "properties" => [
                "one" => [ "filter" => "Passthrough" ],
                "two" => [ "filter" => "Passthrough", "required" => true ]
            ],
            "errors" => [-11 => [
                "code" => -500,
                "message" => "PROP custom error"
            ]]
        ];

        $this->assertCustomError(
            $schema, [
            "one" => "AAA",
            "two" => "BBB"
        ],
            1,
            null
        );

        $this->assertCustomError(
            $schema, [
            "one" => "AAA",
        ],
            -500,
            "PROP custom error"
        );
    }
}
