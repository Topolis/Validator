<?php
/**
 * Created by PhpStorm.
 * User: bulla
 * Date: 08.06.17
 * Time: 13:55
 */

namespace Topolis\Validator;


class ValidatorTest extends \PHPUnit_Framework_TestCase {

    protected function assertValid($schema, $input, $expected){
        $validator = new Validator(__DIR__."/schemas/".$schema);

        $result = $validator->validate($input, false, $errors);

        $this->assertEquals($expected, $result);
        $this->assertEmpty($errors);
    }

    protected function assertInvalid($schema, $input){
        $validator = new Validator(__DIR__."/schemas/".$schema);

        $result = $validator->validate($input, false, $errors);
        $this->assertNull($result);
        $this->assertNotEmpty($errors);
    }


    public function testSimpleValid(){

        $this->assertValid("test-simple.yml", [
            "one" => "A",
            "two" => "B",
            "three" => "C"
        ], [
            "one" => "A",
            "two" => "B",
            "three" => "C"
        ]);

        $this->assertValid("test-simple.yml", [
            "two" => "B",
        ], [
            "one" => '', // Plain filter map's false/null to
            "two" => "B",
            "three" => "threeD"
        ]);

        $this->assertValid("test-simple.yml", [
            "one" => "A",
            "two" => "B",
        ], [
            "one" => "A",
            "two" => "B",
            "three" => "threeD"
        ]);

    }
}
