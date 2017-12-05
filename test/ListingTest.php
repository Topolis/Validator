<?php
/**
 * Created by PhpStorm.
 * User: bulla
 * Date: 08.06.17
 * Time: 11:44
 */

namespace Topolis\Validator\Schema\Node;

use Topolis\Validator\Schema\NodeFactory;

class ListingTest extends \PHPUnit_Framework_TestCase {

    /* @var NodeFactory $factory */
    protected $factory;
    
    protected function setUp() {
        $this->factory = new NodeFactory();
        $this->factory->registerClass(Listing::class);
        $this->factory->registerClass(Object::class);
        $this->factory->registerClass(Value::class);
    }

    protected static $schemaMock = [
        "listing" => [
            "key" => ["filter" => "keyfilter"],
            "value" => ["filter" => "valuefilter"],
            "min" => 24,
            "max" => 456,
            "default" => ["abc" => "def"],
            "required" => true,
            ],
        "conditionals" => [ [
            "condition" => "A == A",
            "listing" => [
                "key" => ["filter" => "Afilter"],
                "max" => 5
            ] ], [
            "condition" => "B == B",
            "listing" =>[
                "key" => ["filter" => "Bfilter"],
                "min" => 2
            ] ]
        ]
    ];

    public function testImport() {
        $listing = new Listing(self::$schemaMock, $this->factory);

        $this->assertInstanceOf("Topolis\Validator\Schema\Node\Listing", $listing);
        $this->assertInstanceOf("Topolis\Validator\Schema\INode", $listing);
    }

    public function testGet(){

        $listing = new Listing(self::$schemaMock, $this->factory);
        $this->assertEquals(456, $listing->getMax());
        $this->assertEquals(24, $listing->getMin());
        $this->assertEquals(["abc" => "def"], $listing->getDefault());
        $this->assertEquals(true, $listing->getRequired());

        $listing = new Listing(self::$schemaMock, $this->factory);
        $this->assertInstanceOf("Topolis\Validator\Schema\Node\Value", $listing->getKey());
        $this->assertInstanceOf("Topolis\Validator\Schema\Node\Value", $listing->getValue());

        $this->assertEquals([
            'filter' => 'keyfilter',
            'options' => [],
            'default' => null,
            'required' => false,
            'remove' => false,
            'strict' => false
        ], $listing->getKey()->export());

        $this->assertEquals([
            'filter' => 'valuefilter',
            'options' => [],
            'default' => null,
            'required' => false,
            'remove' => false,
            'strict' => false
        ], $listing->getValue()->export());

    }

    public function testGetConditionals(){
        $listing = new Listing(self::$schemaMock, $this->factory);

        $conditionals = $listing->getConditionals();

        $this->assertCount(2, $listing->getConditionals());
        $this->assertInstanceOf("Topolis\Validator\Schema\Conditional", $conditionals[0]);
        $this->assertInstanceOf("Topolis\Validator\Schema\Conditional", $conditionals[1]);
    }

    public function testMerge() {
        $schemaMockA = self::$schemaMock;

        $schemaMockB = [
            "listing" => [
                "key" => ["filter" => "keyfilterA"],
                "value" => ["filter" => "keyfilterB"],
                "min" => 123,
                "max" => 456,
                "default" => ["def" => "ghi"],
            ],
            "conditionals" => [ [
                "condition" => "C == C",
                "listing" => [
                    "key" => ["filter" => "keyfilterC"],
                    "max" => 567
                ]
            ] ]
        ];

        $schemaA = new Listing($schemaMockA, $this->factory);
        $schemaB = new Listing($schemaMockB, $this->factory);
        $schemaA->merge($schemaB);

        $this->assertEquals([
                "key" => [
                    "filter" => "keyfilterA",
                    'options' => [],
                    'default' => null,
                    'required' => false,
                    'remove' => false,
                    'strict' => false
                ],
                "value" => [
                    "filter" => "keyfilterB",
                    'options' => [],
                    'default' => null,
                    'required' => false,
                    'remove' => false,
                    'strict' => false
                ],
                "min" => 123,
                "max" => 456,
                "default" => ["def" => "ghi"],
                "required" => true
            ], $schemaA->export()["listing"]);

    }

}
