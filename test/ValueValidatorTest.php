<?php
/**
 * Created by PhpStorm.
 * User: bulla
 * Date: 08.06.17
 * Time: 13:55
 */

namespace Topolis\Validator\Schema\Validators;


use Topolis\Validator\Schema\Node\Listing;
use Topolis\Validator\Schema\Node\Object;
use Topolis\Validator\Schema\Node\Value;
use Topolis\Validator\Schema\NodeFactory;
use Topolis\Validator\StatusManager;

class ValueValidatorTest extends \PHPUnit_Framework_TestCase {

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
        $definition = new Value($definition, $this->factory);
        $validator = new ValueValidator($definition, $errorhandler, $this->factory);

        $result = $validator->validate($input, $data);

        $message = "";
        if($errorhandler->getMessages())
            $message = $errorhandler->getMessages()[0]["message"];

        $this->assertEmpty($errorhandler->getMessages(), $message);
        $this->assertEquals(StatusManager::VALID, $errorhandler->getStatus(), $message);
        $this->assertEquals($expected, $result);
    }

    protected function assertInvalid($definition, $input, $status, $data = []){
        $errorhandler = new StatusManager();
        $definition = new Value($definition, $this->factory);
        $validator = new ValueValidator($definition, $errorhandler, $this->factory);

        $result = $validator->validate($input, $data);

        $message = "";
        if($errorhandler->getMessages())
            $message = $errorhandler->getMessages()[0]["message"];

        $this->assertNotEmpty($errorhandler->getMessages(), $message);
        $this->assertSame($status, $errorhandler->getStatus(), $message);
        $this->assertNotEquals($input, $result);
    }

    public function testValidateFilter(){

        // Valid test
        $this->assertValid([
                "filter" => "Passthrough"
            ],
            "test-value",
            "test-value"
        );

        // Invalid test
        $this->assertInvalid([
            "filter" => "Strip",
            "strict" => true
        ],
            "strip<a>me",
            StatusManager::INVALID
        );

        // Sanitized test
        $this->assertInvalid([
            "filter" => "Strip"
        ],
            "strip<a>me",
            StatusManager::SANITIZED
        );
    }

    public function testValidateFilterConditional(){

        $schema = [
            "conditionals" => [ [
                "condition" => "value == B",
                "filter" => "Test",
                "options" => ["expected" => "expectedB"]
            ],[
                "condition" => "value == C",
                "filter" => "Test",
                "options" => ["expected" => "expectedC"]
            ]
            ],
            "filter" => "Test",
            "strict" => true,
            "options" => ["expected" => "expectedA"]
        ];

        // Condition does not apply
        $this->assertValid(
            $schema,
            "expectedA",
            "expectedA",
            ["value" => "A"]
        );

        // Condition B applies and is valid
        $this->assertValid(
            $schema,
            "expectedB",
            "expectedB",
            ["value" => "B"]
        );

        // Condition B applies and is invalid
        $this->assertInvalid(
            $schema,
            "expectedA",
            StatusManager::INVALID,
            ["value" => "B"]
        );


    }

}
