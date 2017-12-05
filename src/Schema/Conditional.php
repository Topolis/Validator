<?php

namespace Topolis\Validator\Schema;

use Topolis\FunctionLibrary\Collection;
use Topolis\Validator\ConditionParser;

class Conditional {

    protected $mode;
    protected $condition;
    /* @var ConditionParser $parser */
    protected $parser;
    /* @var INode $node */
    protected $node;
    /* @var NodeFactory $factory */
    protected $factory;

    const MODE_REPLACE = "replace";
    const MODE_MERGE = "merge";

    /**
     * Conditional constructor.
     * @param array $schema the schema definition of this conditional
     * @param array $base the base definition that will be modified
     * @param NodeFactory $factory
     */
    public function __construct(array $schema, array $base, NodeFactory $factory) {
        $this->factory = $factory;
        $this->import($schema, $base);
    }

    public function setParser(ConditionParser $parser){
        $this->parser = $parser;
    }

    public function import(array $schema, array $base){

        $this->condition = Collection::get($schema, "condition", false);
        $this->mode = Collection::get($schema, "mode", self::MODE_MERGE);

        switch($this->mode){
            case self::MODE_MERGE:
                $schema = array_replace_recursive($base, $schema);
                break;
            case self::MODE_REPLACE:
                break;
        }

        unset($schema["conditionals"]);
        unset($schema["condition"]);
        unset($schema["mode"]);

        $this->node = $this->factory->createNode($schema);
    }

    public function export(){
        return [
            "condition" => $this->condition,
            "mode" => $this->mode
        ] + $this->node->export();
    }

    // ----------------------------------------------------------------

    public function getNode(){
        return $this->node;
    }

    public function evaluate($data){
        // TODO: Remove this line and allow parsers to be set from outside during schema load (We cant do this globaly atm as we do not want statics or globals)
        $parser = $this->parser ? $this->parser : new ConditionParser();

        return $parser->parse($this->condition, $data);
    }


}