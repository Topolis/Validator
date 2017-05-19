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

use Exception;
use Topolis\FunctionLibrary\Collection;

/**
 * ConditionParser
 * Parse a short string and evaluate it's condition
 *
 * condition syntax:
 *     <path> <condition> <value>
 *     - <path>        an array path compatilbe with Collection::get()
 *     - <condition>   a single word from the list of known operators: < > <= => == != in notin
 *     - <value>       a json compatible value: "hello world" or ["one","two"] or similar
 *
 * @package Topolis
 * @subpackage Validator
 */

class ConditionParser {

    const LESS      = '<';
    const GREATER   = '>';
    const LESSEQ    = '<=';
    const GREATEREQ = '=>';
    const EQUALS    = '==';
    const NOT       = '!=';
    const IN        = 'in';
    const NOTIN     = 'notin';

    public function __construct() {
    }

    /**
     * @param string $condition a condition string as seen above
     * @param mixed $data a value to check the condition against
     * @return bool
     * @throws Exception
     */
    public function parse($condition, $data) {

        if(!is_string($condition))
            throw new Exception("Invalid condition found - Not a string");

        $parts = str_getcsv($condition, ' ', '"', '\\');

        if(count($parts) != 3)
            throw new Exception("Invalid condition found - Not three elements");

        list($key, $operator, $value) = $parts;

        $data = $this->getData($key, $data);
        $result = $this->evaluate($data, $operator, $value);

        return $result;
    }

    /**
     * Return the value at $key from $data
     * @param string $key
     * @param array $data
     * @return array|mixed|null
     */
    protected function getData($key, array $data){
        return Collection::get($key, $data, null);
    }

    /**
     * Evaluate expression
     * @param mixed $data
     * @param string $operator
     * @param mixed $value
     * @return bool
     * @throws Exception
     */
    protected function evaluate($data, $operator, $value){

        switch($operator){

            case self::EQUALS:
                return $data == $value;

            case self::LESS:
                return $data < $value;

            case self::GREATER:
                return $data > $value;

            case self::LESSEQ:
                return $data <= $value;

            case self::GREATEREQ:
                return $data >= $value;

            case self::NOT:
                return $data != $value;

            case self::IN:
                if(!is_array($value))
                    throw new Exception("Invalid condition found - ".$operator." operator requires array value");
                return in_array($data, $value);

            case self::NOTIN:
                if(!is_array($value))
                    throw new Exception("Invalid condition found - ".$operator." operator requires array value");
                return !in_array($data, $value);

            default:
                throw new Exception("Invalid condition found - Bad operator");
        }

    }

}