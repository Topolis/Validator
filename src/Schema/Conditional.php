<?php

namespace Topolis\Validator\Schema;

use Topolis\FunctionLibrary\Collection;
use Topolis\Validator\ConditionParser;
use Topolis\Validator\Traits\MagicGetSet;

class Conditional {

    use MagicGetSet;

    protected $condition;

    /* @var \Topolis\Validator\Schema\Object | \Topolis\Validator\Schema\Value $definition */
    protected $definition;
    protected $definitionType = false;

    protected $parser;

    public function __construct(array $data, $definitionType) {
        $this->definitionType = $definitionType;
        $this->import($data);
    }

    public function setParser(ConditionParser $parser){
        $this->parser = $parser;
    }

    public function import(array $definition){
        $this->condition = Collection::get($definition, "condition", false);
        unset($definition["condition"]);

        $this->definition = new $this->definitionType($definition);
    }

    public function export(){
        return [ "condition" => $this->condition ] + $this->definition->export();
    }

    // ----------------------------------------------------------------

    public function getDefinition(){
        return $this->definition;
    }

    public function evaluate($data){
        // TODO: Remove this line and allow parsers to be set from outside during schema load (We cant do this globaly atm as we do not want statics or globals)
        $parser = $this->parser ? $this->parser : new ConditionParser();

        return $parser->parse($this->condition, $data);
    }


}