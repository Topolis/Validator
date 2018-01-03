<?php
/**
 * Created by PhpStorm.
 * User: bulla
 * Date: 08.06.17
 * Time: 13:55
 */

namespace Topolis\Validator;

use ReflectionClass;

class ConditionParserTest extends \PHPUnit_Framework_TestCase {

    public function testRelativePaths() {
        $statusManager = new StatusManager();

        $parser = new ConditionParser($statusManager);
        $method = self::getMethod("getData", "\Topolis\Validator\ConditionParser");

        $data = [
            "aone" => "value-aone",
            "awto" => "value-atwo",
            "athree" => [
                "bone" => "value-bone",
                "btwo" => "value-btwo",
                "bthree" => [
                    "cone" => "value-cone",
                    "ctwo" => "value-ctwo",
                    "cthree" => [
                        "done" => "value-done",
                        "dtwo" => "value-dtwo"
                    ]
                ]
            ]
        ];

        $statusManager->enterPath("athree");
        $statusManager->enterPath("bthree");

        // Absolute paths
        $this->assertEquals("value-aone", $method->invokeArgs($parser, ["/aone", $data]));
        $this->assertEquals("value-btwo", $method->invokeArgs($parser, ["/athree/btwo", $data]));
        $this->assertEquals("value-ctwo", $method->invokeArgs($parser, ["/athree/bthree/ctwo", $data]));

        // Relative
        $this->assertEquals("value-ctwo", $method->invokeArgs($parser, ["ctwo", $data]));
        $this->assertEquals("value-ctwo", $method->invokeArgs($parser, ["./ctwo", $data]));
        $this->assertEquals("value-btwo", $method->invokeArgs($parser, ["../btwo", $data]));
        $this->assertEquals("value-aone", $method->invokeArgs($parser, ["../../aone", $data]));
        $this->assertEquals("value-btwo", $method->invokeArgs($parser, ["../../athree/./bthree/../btwo/.", $data]));

    }

    public function testParse() {

        $statusManager = new StatusManager();
        $parser = new ConditionParser($statusManager);

        $data = [
            "aone" => "value-aone",
            "awto" => 234,
            "athree" => [
                "bone" => "value bone",
                "btwo" => true,
                "bthree" => [
                    "cone" => 98236,
                    "ctwo" => false
                ]
            ]
        ];

        // string equals
        $this->assertTrue(  $parser->parse('/aone == "value-aone"', $data),         'aone == "value-aone"');
        $this->assertTrue(  $parser->parse("/aone == value-aone", $data),           "aone == value-aone");
        $this->assertFalse( $parser->parse("/aone == something-wrong", $data),      "aone == something-wrong");
        $this->assertTrue(  $parser->parse('/athree/bone == "value bone"', $data),  '/athree/bone == "value bone"');

        // int
        $this->assertTrue(  $parser->parse('/awto == 234', $data), 'awto == 234');
        $this->assertFalse( $parser->parse('/awto == 467', $data), 'awto == 467');
        $this->assertTrue(  $parser->parse('/awto > 233', $data),  'awto > 233');
        $this->assertFalse( $parser->parse('/awto > 234', $data),  'awto > 234');
        $this->assertTrue(  $parser->parse('/awto < 235', $data),  'awto < 235');
        $this->assertFalse( $parser->parse('/awto < 234', $data),  'awto < 234');
        $this->assertTrue(  $parser->parse('/awto <= 234', $data), 'awto <= 234');
        $this->assertTrue(  $parser->parse('/awto <= 250', $data), 'awto <= 250');
        $this->assertTrue(  $parser->parse('/awto >= 234', $data), 'awto >= 234');
        $this->assertTrue(  $parser->parse('/awto >= 233', $data), 'awto >= 233');
        $this->assertFalse( $parser->parse('/awto >= 235', $data), 'awto >= 235');
        $this->assertTrue(  $parser->parse('/awto != 235', $data), 'awto != 235');
        $this->assertFalse( $parser->parse('/awto != 234', $data), 'awto != 235');

        // array in/notin
        $this->assertTrue(  $parser->parse('/awto in (1,234,3)', $data),     'awto in (1,234,3)');
        $this->assertTrue(  $parser->parse('/awto in (1, 234 , 3)', $data),  'awto in (1, 234 , 3)');
        $this->assertTrue(  $parser->parse('/awto in (234,1,3)', $data),     'awto in (234,1,3)');
        $this->assertTrue(  $parser->parse('/awto in (234,234,234)', $data), 'awto in (234,234,234)');
        $this->assertFalse( $parser->parse('/awto in (1,2,3)', $data),       'awto in (1,2,3)');

        $this->assertTrue(  $parser->parse('/aone in (1,value-aone,something)', $data),           'aone in (1,value-aone,something)');
        $this->assertTrue(  $parser->parse('/aone in (1, "value-aone" , \'something\')', $data),  'aone in (1, "value-aone" , \'something\')');
        $this->assertTrue(  $parser->parse('/aone in (\'value-aone\',1,something)', $data),       'aone in (\'value-aone\',1,something)');
        $this->assertTrue(  $parser->parse('/aone in (value-aone,value-aone,value-aone)', $data), 'aone in (value-aone,value-aone,value-aone)');
        $this->assertFalse( $parser->parse('/aone in (1,something,3)', $data),                    'aone in (1,something,3)');

        $this->assertTrue(  $parser->parse('/awto notin (1,2,3)', $data),                  'awto notin (1,2,3)');
        $this->assertTrue(  $parser->parse('/awto notin (a,b,c)', $data),                  'awto notin (a,b,c)');
        $this->assertFalse( $parser->parse('/awto notin (a,234,3)', $data),                'awto notin (a,234,3)');
        $this->assertTrue(  $parser->parse('/aone notin (1,else,something)', $data),       'aone in (1,else,something)');
        $this->assertFalse( $parser->parse('/aone notin (1,value-aone,something)', $data), 'aone in (1,value-aone,something)');

        // Some complex paths
        $this->assertTrue(  $parser->parse('/athree/bthree/cone == 98236', $data), '/athree/bthree/cone == 98236');

        // boolean
        $this->assertTrue(  $parser->parse('/athree/btwo == true', $data),         'athree/btwo == true');
        $this->assertTrue(  $parser->parse('/athree/bthree/ctwo == false', $data), 'athree/bthree/ctwo == false');
        $this->assertTrue(  $parser->parse('/athree/bthree/ctwo != true', $data),  'athree/bthree/ctwo != true');
        $this->assertTrue(  $parser->parse('/awto == true', $data),                'awto == true');
        $this->assertFalse( $parser->parse('/awto == "true"', $data),              'awto == "true"');
    }

    /**
     * Helper method to make a protected method public during testing
     * @param $name
     * @return \ReflectionMethod
     */
    protected static function getMethod($name, $class) {
        $class = new ReflectionClass($class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

}
