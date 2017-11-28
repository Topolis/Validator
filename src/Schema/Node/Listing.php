<?php

namespace Topolis\Validator\Schema\Node;

use Exception;
use Topolis\Validator\Schema\Conditional;
use Topolis\Validator\Schema\INode;
use Topolis\Validator\Schema\NodeFactory;
use Topolis\Validator\Schema\Validators\ListingValidator;

class Listing implements INode {

    /* @var NodeFactory $factory */
    protected $factory;

    /* @var Conditional[] $conditionals */
    protected $conditionals = [];

    protected $default;
    protected $required;
    protected $min = false;
    protected $max = false;

    /* @var Value $key */
    protected $key;
    /* @var INode $value */
    protected $value;

    /**
     * Schema constructor.
     * @param array $data
     * @param NodeFactory $factory
     */
    public function __construct(array $data, NodeFactory $factory) {
        $this->factory = $factory;
        $this->import($data);
    }

    public static function detect(array $schema) {
        return is_array($schema) and isset($schema["listing"]);
    }

    public static function validator() {
        return ListingValidator::class;
    }


    /**
     * @param array $data
     * @throws Exception
     */
    public function import(array $data){

        $data = $data["listing"] + [
                "conditionals" => [],
                "default" => null,
                "required" => false,
                "min" => false,
                "max" => false,
                "key" => [],
                "value" => [],
            ];

        $this->default = $data["default"];
        $this->required = $data["required"];

        foreach($data["conditionals"] as $conditional){
            $this->conditionals[] = new Conditional($conditional, $data, $this->factory);
        }

        $this->key = $this->factory->createNode($data["key"]);
        $this->value = $this->factory->createNode($data["value"]);

        if(!$this->key instanceof Value)
            throw new Exception("Key for listing must be of type value");
    }

    /**
     * @return array
     */
    public function export() {
        $export = [
            "conditionals" => [],
            "default" => $this->getDefault(),
            "required" => $this->getRequired(),
            "min" => $this->getMin(),
            "max" => $this->getMax(),
            "key" => $this->getKey(),
            "value" => $this->getValue()
        ];

        foreach($this->conditionals as $conditional)
            $export["conditionals"][] = $conditional->export();

        return $export;
    }

    // ----------------------------------------------------------------

    /**
     * @return Conditional[]
     */
    public function getConditionals(){
        return $this->conditionals;
    }

    /**
     * @return mixed
     */
    public function getDefault(){
        return $this->default;
    }

    /**
     * @return mixed
     */
    public function getRequired(){
        return $this->required;
    }

    /**
     * @return mixed
     */
    public function getMin(){
        return $this->min;
    }

    /**
     * @return mixed
     */
    public function getMax(){
        return $this->max;
    }

    /**
     * @return mixed
     */
    public function getKey(){
        return $this->key;
    }

    /**
     * @return mixed
     */
    public function getValue(){
        return $this->value;
    }

    /**
     * @param INode $schema
     */
    public function merge(INode $schema){

        /* @var Object $schema */

        $this->default  = $schema->getDefault()  ? $schema->getDefault()  : $this->default;
        $this->required = $schema->getRequired() ? $schema->getRequired() : $this->required;
        $this->min      = $schema->getMin()      ? $schema->getMin()      : $this->min;
        $this->max      = $schema->getMax()      ? $schema->getMax()      : $this->max;

        $this->key      = $schema->getkey()      ? $schema->getkey()      : $this->key;
        $this->value    = $schema->getValue()    ? $schema->getValue()    : $this->value;

        // Conditionals (Cant be smartly merged as we dont have a key for them (Might be a CR?)
        $this->conditionals = array_merge($this->conditionals, $schema->getConditionals());

    }

}