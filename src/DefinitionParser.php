<?php
/**
 * Validator
 * Validate and filter an array according to field definitions in yaml form
 * @author Tobias Bulla
 * @copyright ToBe - 2017
 * @package Topolis
 * @subpackage Validator
 */

namespace Topolis\Validator;

/**
 * DefinitionParser
 * Parse an object definition
 * @package Topolis
 * @subpackage Validator
 */

class DefinitionParser{

    /* @var ConditionParser $conditionParser */
    protected $conditionParser = null;

    /* @var array $defaultDefinition       default values for field definitions */
    protected static $defaultDefinition = [
        "filter" => "passthrough",
        "filter-options" => [],
        "default" => null,
        "required" => false,
        "remove" => false
    ];

    /* @var array $defaultDefinition       default values for field definitions with sub fields */
    protected static $defaultSubDefinition = [
        "type" => "single",
        "conditionals" => []
    ];

    /* @var array $defaultRemoves       default values to remove if removal is set */
    protected static $defaultRemoves = [
        null,
        ""
    ];

    public function __construct() {
    }

    public function parse(array $definitions) {

        foreach($definitions as $key => &$definition){

            $definition = $definition + self::$defaultDefinition;

            if( $definition["remove"] === true )
                $definition["remove"] = [];

            if( is_array($definition["remove"]) )
                $definition["remove"] = array_merge($definition["remove"], self::$defaultRemoves );

            $this->parseDefinitions($definition);
            $this->parseConditionals($definition);
        }

        return $definitions;
    }

    protected function parseDefinitions(&$definition){

        if(!isset($definition["definitions"]))
            return;

        $definition = $definition + self::$defaultSubDefinition;
        $definition["definitions"] = $this->parse($definition["definitions"]);
    }

    protected function parseConditionals(&$definition){

        if(!isset($definition["conditionals"]))
            return;

        foreach($definition["conditionals"] as $conditional){

            $apply = $this->conditionParser->parse($conditional["condition"], $data);

            $replacements =& $conditional;
            unset($replacements["condition"]);

            if($apply)
                $definition = $replacements + $definition;
        }
    }

}