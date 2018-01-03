<?php

namespace Topolis\Validator\Schema\Node;

use Topolis\Validator\Schema\Conditional;
use Topolis\Validator\Schema\INode;
use Topolis\Validator\Schema\NodeFactory;
use Topolis\Validator\Schema\Validators\ValueValidator;

class Value implements INode {

    /* @var NodeFactory $factory */
    protected $factory;

    protected $filter = self::FILTER_DEFAULT;
    protected $options = [];
    protected $default = null;
    protected $required = false;
    protected $strict = false;
    protected $conditionals = [];

    const FILTER_DEFAULT = "Passthrough";

    /**
     * Field constructor.
     * @param array $data
     * @param NodeFactory $factory
     */
    public function __construct(array $data, NodeFactory $factory) {
        $this->factory = $factory;
        $this->import($data);
    }

    public static function detect(array $schema) {
        return is_array($schema) and isset($schema["filter"]);
    }

    public static function validator() {
        return ValueValidator::class;
    }

    /**
     * @param array $data
     */
    public function import(array $data){

        $data = $data + [
                "filter" => self::FILTER_DEFAULT,
                "options" => [],
                "default" => null,
                "required" => false,
                "strict" => false,
                "conditionals" => []
            ];

        $this->filter = $data["filter"];
        $this->options = $data["options"];
        $this->default = $data["default"];
        $this->required = $data["required"];
        $this->strict = $data["strict"];

        foreach($data["conditionals"] as $conditional){
            $this->conditionals[] = new Conditional($conditional, $data, $this->factory);
        }
    }

    /**
     * @return array
     */
    public function export() {
        $export = [
            "filter" => $this->getFilter(),
            "options" => $this->getOptions(),
            "default" => $this->getDefault(),
            "required" => $this->getRequired(),
            "strict" => $this->getStrict()
        ];

        foreach($this->conditionals as $conditional)
            $export["conditionals"][] = $conditional->export();

        return $export;
    }

    // ----------------------------------------------------------------

    /**
     * @return string
     */
    public function getFilter(){
        return $this->filter;
    }

    /**
     * @return array
     */
    public function getOptions(){
        return $this->options;
    }

    /**
     * @return null
     */
    public function getDefault(){
        return $this->default;
    }

    /**
     * @return bool
     */
    public function getRequired(){
        return $this->required;
    }

    /**
     * @return bool
     */
    public function getStrict(){
        return $this->strict;
    }

    /**
     * @return Conditional[]
     */
    public function getConditionals(){
        return $this->conditionals;
    }

    /**
     * @param INode $schema
     */
    public function merge(INode $schema){
        /* @var Value $schema */
        $this->filter = $schema->getFilter() !== self::FILTER_DEFAULT ? $schema->getFilter() : $this->filter;
        $this->options = array_merge($this->options, $schema->getOptions());
        $this->default = $schema->getDefault() ? $schema->getDefault() : $this->default;
        $this->required = $schema->getRequired() ? $schema->getRequired() : $this->required;
    }

}