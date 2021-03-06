<?php
/**
 * Created by PhpStorm.
 * User: bulla
 * Date: 08.06.17
 * Time: 13:55
 */

namespace Topolis\Validator;


use Symfony\Component\Yaml\Yaml;

class ValidatorTest extends \PHPUnit_Framework_TestCase {

    protected function assertValid($schema, $input, $expected){
        $validator = new Validator(__DIR__."/schemas/".$schema);

        $result = $validator->validate($input, false);
        $errors = $validator->getMessages();

        $this->assertEquals($expected, $result);
        $this->assertEmpty($errors);
    }

    protected function assertInvalid($schema, $input){
        $validator = new Validator(__DIR__."/schemas/".$schema);

        $result = $validator->validate($input, false);
        $errors = $validator->getMessages();

        $this->assertNull($result);
        $this->assertNotEmpty($errors);
    }

    public function testSchemas()
    {

        $config = Yaml::parse(file_get_contents(__DIR__ . "/schemas/_config.yml"));

        foreach ($config["tests"] as $test) {

            $Validator = new Validator(__DIR__ . "/schemas/" . $test["schema"]);

            foreach ($test["input"] as $testconfig) {

                list($input, $expected, $expectedStatus) = $testconfig;

                $inputData = json_decode(file_get_contents(__DIR__ . "/schemas/" . $input), true);
                $expectedData = $expected ? json_decode(file_get_contents(__DIR__ . "/schemas/" . $expected), true) : $expected;

                $result = $Validator->validate($inputData, true);
                $status = $Validator->getStatus();
                $messages = $Validator->getMessages();

                $debug = "Messages:\n";
                if ($messages) {
                    foreach ($messages as $message)
                        $debug .= "  - " . $message["message"] . " - at '" . implode(".", $message["path"])
                            //."' with value ".var_export($message["value"],true)
                            . "\n";;
                }

                $this->assertEquals($expectedData, $result, "Invalid result for schema '" . $test["schema"] . "' and data '" . $input . "'\n" . $debug);
                $this->assertEquals($expectedStatus, $status, "Invalid status for schema '" . $test["schema"] . "' and data '" . $input . "'\n" . $debug);

                if ($status == StatusManager::VALID)
                    $this->assertEmpty($messages, "Messages found for schema '" . $test["schema"] . "' and data '" . $input . "'");
                else
                    $this->assertNotEmpty($messages, "Messages not found for schema '" . $test["schema"] . "' and data '" . $input . "'");
            }
        }
    }

    /**
     * @throws \Exception
     */
    public function testParsers(){
        $parser = function($input){ return [
            'properties' => [
                'test' => [
                    "filter" => "Test",
                    "options" => ["expected" => $input]
                ]
            ]
        ]; };

        // Default Parser
        Validator::setDefaultParser($parser);

        $Validator = new Validator('test-parser-a');
        $result = $Validator->validate(['test' => 'test-parser-a'], true);
        $this->assertEquals(['test' => 'test-parser-a'], $result, "Default parser - valid");

        $Validator = new Validator('test-parser-b');
        $result = $Validator->validate(['test' => 'test-parser-a'], true);
        $this->assertEquals(StatusManager::INVALID, $Validator->getStatus(), "Default parser - invalid");

        // Set Parser / manual loading of schema
        $Validator = new Validator();
        $Validator->setParser($parser);

        $Validator->loadSchema('test-parser-c');
        $result = $Validator->validate(['test' => 'test-parser-c'], true);
        $this->assertEquals(['test' => 'test-parser-c'], $result, "Set parser - one");

        $Validator->loadSchema('test-parser-d');
        $result = $Validator->validate(['test' => 'test-parser-d'], true);
        $this->assertEquals(['test' => 'test-parser-d'], $result, "Set parser - two");
    }

}
