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
        "type" => "single"
    ];

    /* @var array $defaultRemoves       default values to remove if removal is set */
    protected static $defaultRemoves = [
        null,
        ""
    ];

    public function parse(array $definitions) {

        foreach($definitions as $key => &$definition){

            $definition = $definition + self::$defaultDefinition;

            if(isset($definition["definitions"])) {
                $definition = $definition + self::$defaultSubDefinition;
                $definition["definitions"]    = $this->parse($definition["definitions"]);
            }

            if( $definition["remove"] === true )
                $definition["remove"] = [];

            if( is_array($definition["remove"]) )
                $definition["remove"] = array_merge($definition["remove"], self::$defaultRemoves );
        }

        return $definitions;
    }

}