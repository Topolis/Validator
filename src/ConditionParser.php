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

    /* @var StatusManager $statusManager */
    protected $StatusManager;

    const LESS      = '<';
    const GREATER   = '>';
    const LESSEQ    = '<=';
    const GREATEREQ = '>=';
    const EQUALS    = '==';
    const NOT       = '!=';
    const IN        = 'in';
    const NOTIN     = 'notin';

    const PATH_SEPARATOR = '/';
    const PATH_RELATIVE = '.';
    const PATH_BACK = '..';

    public function __construct(StatusManager $StatusManager) {
        $this->StatusManager = $StatusManager;
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

        $match = preg_match('/^([a-z.\-\_'.preg_quote(self::PATH_RELATIVE.self::PATH_BACK.self::PATH_SEPARATOR, "/").']+) (<|>|==|<=|>=|!=|in|notin) (.+)$/i', $condition, $matches);
        if(!$match)
            throw new Exception("Invalid condition found");

        $key = $matches[1];
        $operator = $matches[2];
        $value = $matches[3];

        $value = $this->parseValue($value);

        $data = $this->getData($key, $data);
        $result = $this->evaluate($data, $operator, $value);

        return $result;
    }

    protected function parseValue($value){

        // value is a boolean
        if($value == "true")
            return true;
        if($value == "false")
            return false;

        // value is an array
        if(preg_match('/^\(.*\)$/', $value)) {
            $value = explode(",", trim($value, "()"));
            array_walk($value, function(&$item){
                $item = trim($item, ' "\'');
            });
            return $value;
        }

        // value is a plain string
        return trim($value, ' "\'');
    }

    /**
     * Return the value at $key from $data
     * @param string $key
     * @param array $data
     * @return array|mixed|null
     */
    protected function getData($path, array $data){

        $path = $this->resolvePath($path);

        try{
            return Collection::getFromPath($data, $path, self::PATH_SEPARATOR);
        }
        catch(Exception $e) {
            return null;
        }
    }

    protected function resolvePath($path) {

        // Absolute path with no backwards or local nodes
        if(strpos($path, self::PATH_SEPARATOR) === 0 &&
           strpos($path, self::PATH_BACK) === false &&
           strpos($path, self::PATH_RELATIVE) === false)
            return trim($path, self::PATH_SEPARATOR);

        // Prefix with current absolute path
        $path = array_merge($this->StatusManager->getPath(), explode(self::PATH_SEPARATOR, $path));

        // Resolve path
        $resolved = [];
        foreach($path as $idx => $node) {
            switch((string)$node){

                case self::PATH_RELATIVE;
                    break;

                case self::PATH_BACK;
                    array_pop($resolved);
                    break;

                default:
                    array_push($resolved, $node);
            }
        }

        return implode(self::PATH_SEPARATOR, $resolved);
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
                throw new Exception("Invalid condition found - Bad operator (".$operator.")");
        }

    }

}