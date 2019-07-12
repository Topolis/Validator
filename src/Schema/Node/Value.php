<?php

namespace Topolis\Validator\Schema\Node;

use Topolis\Validator\Schema\Conditional;
use Topolis\Validator\Schema\INode;
use Topolis\Validator\Schema\NodeFactory;
use Topolis\Validator\Schema\Validators\ValueValidator;

class Value extends BaseNode implements INode {

    protected $filter = self::FILTER_DEFAULT;
    protected $options = [];
    protected $strict = false;

    const FILTER_DEFAULT = "Passthrough";

    public static function detect(array $schema) {
        return \is_array($schema) and isset($schema["filter"]);
    }

    public static function validator() {
        return ValueValidator::class;
    }

    /**
     * @param array $data
     */
    public function import(array $data){

        parent::import($data);

        $data += [
            "filter" => self::FILTER_DEFAULT,
            "options" => [],
            "strict" => false,
        ];

        $this->filter = $data["filter"];
        $this->options = $data["options"];
        $this->strict = $data["strict"];
    }

    /**
     * @return array
     */
    public function export() {
        $export = parent::export();
        $export += [
            "filter" => $this->getFilter(),
            "options" => $this->getOptions(),
            "strict" => $this->getStrict()
        ];

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
     * @return bool
     */
    public function getStrict(){
        return $this->strict;
    }

    /**
     * @param INode $schema
     */
    public function merge(INode $schema){
        /* @var Value $schema */
        $this->filter = $schema->getFilter() !== self::FILTER_DEFAULT ? $schema->getFilter() : $this->filter;
        $this->options = array_merge($this->getOptions(), $schema->getOptions());
        $this->default = $schema->getDefault() ? $schema->getDefault() : $this->default;
        $this->required = $schema->getRequired() ? $schema->getRequired() : $this->required;

        $this->errors += $schema->getErrors();
    }

}