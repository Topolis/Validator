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
use Topolis\Filter\Filter;
use Topolis\FunctionLibrary\Collection;

/**
 * DefinitionParser
 * Parse an object definition
 * @package Topolis
 * @subpackage Validator
 */

class ValueProcessor {

    protected $definitions = [];
    protected $errors = [];
    protected $path = [];

    public function setDefinitions(array $definitions){
        $this->definitions = $definitions;
    }

    public function getErrors(){
        return $this->errors;
    }

    public function validate($values){
        $this->errors = [];
        $this->path = [];

        return $this->process($values, $this->definitions);
    }

    protected function process($values, array $definitions){

        $valid = array();

        foreach($definitions as $key => $definition){
            $value = Collection::get($values, $key, $definition["default"]);
            $this->path[] = $key;

            try {
                // Field contains definitions for multiple sub fields directly below this item
                if (isset($definition["definitions"]) && is_array($value) && $definition["type"] == "multiple") {
                    $value = $this->processArrayMultiple($value, $definition);
                } // Field contains definitions for multiple sub items with fields each
                elseif (isset($definition["definitions"]) && is_array($value) && $definition["type"] == "single") {
                    $value = $this->processArraySingle($value, $definition);
                } // Field contains a single value and no sub fields
                elseif (!isset($definition["definitions"]) && !is_array($value)) {
                    $value = $this->processValue($value, $definition);
                }

                // Empty value as specified in "remove" - We always treat "" or null as empty
                $remove = $definition["remove"] !== false && in_array($value, $definition["remove"]);

                if ($definition["required"] && ($value == null || $remove))
                    throw new ValidatorException("Required field is missing");

                if(!$remove)
                    Collection::set($valid, $key, $value);

            } catch (ValidatorException $e) {
                // Error reporting
                $this->errors[ implode(".",$this->path) ] = [
                    "error" => $e->getMessage(),
                    "value" => $value,
                    "definition" => $definition
                ];
            }

            array_pop($this->path);
        }

        return $valid;
    }

    /**
     * Validate an array of sub arrays with fields in a value with one same definition for all sub arrays
     * example: [ [id: 1, name: "max"], [id: 2, name: "hans"] ]
     *
     * @param $value      array of objects
     * @param array $definition
     * @return array
     */
    protected function processArrayMultiple($value, array $definition){
        $children = array();
        foreach($value as $idx => $child) {

            $this->path[] = $idx;

            $idx = Filter::filter($idx, $definition["filter"], $definition["filter-options"]);
            if($idx !== null && is_array($child))
                $children[$idx] = $this->process($child, $definition["definitions"]);

            array_pop($this->path);
        }
        return $children;
    }

    /**
     * Validate an array of sub fields in a value with a definition for each field
     * example: [id: 1, name: "max"]
     *
     * @param $value      array of objects
     * @param array $definition
     * @return array
     */
    protected function processArraySingle($value, array $definition){
        return $this->process($value, $definition["definitions"]);
    }

    /**
     * Validate a field
     * example: "hello"
     * @param $value
     * @param array $definition
     * @return mixed
     * @throws ValidatorException
     */
    protected function processValue($value, array $definition){
        $strict = Collection::get($definition, "strict", true);
        $original = $value;
        $value = Filter::filter($value, $definition["filter"], $definition["filter-options"]);

        if($strict && $value != $original)
            throw new ValidatorException("Invalid value found");

        return $value;
    }


}