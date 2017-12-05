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


    public function testSchemas(){

        $tests = [];

        $d = dir(__DIR__."/schemas");
        while (false !== ($entry = $d->read())) {

            $ext = pathinfo($entry, PATHINFO_EXTENSION);
            $file = pathinfo($entry, PATHINFO_FILENAME);

            if($ext == "yml"){

                $schema   = $d->path."/".$file.".yml";
                $input    = json_decode( file_get_contents( $d->path."/".$file."-in.json" ), true);
                $expected = json_decode( file_get_contents( $d->path."/".$file."-out.json" ), true);

                $this->assertValid($schema, $input, $expected);

            }
        }
        $d->close();
    }
}
