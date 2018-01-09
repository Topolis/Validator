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
use Topolis\Validator\Schema\Node\Properties;
use Topolis\Validator\StatusManager;

class ListingValidatorTest extends \PHPUnit_Framework_TestCase {

    /* @var NodeFactory $factory */
    protected $factory;

    protected function setUp() {
        $this->factory = new NodeFactory();
        $this->factory->registerClass(Listing::class);
        $this->factory->registerClass(Properties::class);
        $this->factory->registerClass(Value::class);
    }

    protected function assertValid($definition, $input, $expected, $data = []){
        $errorhandler = new StatusManager();
        $definition = new Listing($definition, $this->factory);
        $validator = new ListingValidator($definition, $errorhandler, $this->factory);

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
        $definition = new Listing($definition, $this->factory);
        $validator = new ListingValidator($definition, $errorhandler, $this->factory);

        $result = $validator->validate($input, $data);

        $message = "";
        if($errorhandler->getMessages())
            $message = $errorhandler->getMessages()[0]["message"]." - Value: '".(!is_array($errorhandler->getMessages()[0]["value"]) ? $errorhandler->getMessages()[0]["value"] : "array")."' path: ".implode(".",$errorhandler->getPath());

        $this->assertNotEmpty($errorhandler->getMessages(), $message);
        $this->assertSame($status, $errorhandler->getStatus(), $message);
        $this->assertNotEquals($input, $result);
    }

    public function testValidateFilterFields(){

        $schema = [
            "listing" => [
                "min" => 1,
                "max" => 3,
                "key" => [
                    "filter" => "Test",
                    "options" => ["expected" => ["K1","K2","K3","K4"]]
                ],
                "value" => [
                    "filter" => "Test",
                    "options" => ["expected" => ["V1","V2","V3","V4"]],
                    "default" => "V1"
                ],
            ]
        ];

        // Valid test
        $this->assertValid(
            $schema,
            ["K3" => "V2","K1" => "V3"],
            ["K3" => "V2","K1" => "V3"]
        );

        // Valid test with default
        $this->assertValid(
            $schema,
            ["K1" => "V2","K3" => null],
            ["K1" => "V2","K3" => "V1"]
        );

        // Invalid test - wrong value
        $this->assertInvalid(
            $schema,
            ["K1" => "V2","K3" => "V5"],
            StatusManager::INVALID
        );

        // Invalid test - min
        $this->assertInvalid(
            $schema,
            [],
            StatusManager::INVALID
        );

        // Invalid test - max
        $this->assertInvalid(
            $schema,
            ["K1" => "V2","K3" => "V3","K2" => "V3","K4" => "V1"],
            StatusManager::INVALID
        );

        // Valid test - min/zero
        $schema["listing"]["min"] = 0;
        $this->assertValid(
            $schema,
            [],
            []
        );

    }

}
