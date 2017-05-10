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

use Topolis\FunctionLibrary\Collection;
use Topolis\Filter\Filter;
use Exception;

/**
 * AppException
 * Base class for all exceptions. Defines all error codes and can be converted to XML
 * @package Topolis
 * @subpackage Validator
 */

class Validator {

    protected $identity = null;
    protected $config = array();

    /**
     * @param string $identity          name of object and yml file (excluding extensions)
     */
    public function __construct($identity){
        $this->identity = $identity;
        $config = $this->getCached();
        if(!$config){
            $config = $this->getYaml();
            $this->setCached($config);
        }
        $this->config = $config;
    }

    // ------------------------------------------------------------------------------------------------------

    /**
     * Validate $array of values according to field definitions
     * @param array $values            values to validate
     * @param bool $quiet              throw exception on error or just quietly return false
     * @return array|bool              false (if $quiet and invalid values) or validated $array including defaults
     * @throws Exception
     */
    public function validate(array $values, $quiet = false, &$errors = array()){
        $errors = array();

        $valid = $this->validateArray($values, $this->config["definitions"], $errors, $quiet);

        if($errors){
            if(!$quiet)
                throw new Exception("Values for ".$this->identity." object invalid");
            else
                return false;
        }

        return $valid;
    }

    protected function validateArray(array $values, array $definitions, array &$errors){
        $valid = array();

        foreach($definitions as $key => $definition){
            $value = Collection::get($values, $key, $definition["default"]);

            // Field contains definitions for multiple sub fields directly below this item
            if(isset($definition["definitions"]) && is_array($value) && $definition["type"] == "multiple"){
                $children = array();
                foreach($value as $idx => $child) {
                    $idx = Filter::filter($idx, $definition["filter"], $definition["filter-options"]);
                    if($idx !== null && is_array($child))
                        /** @noinspection PhpIllegalArrayKeyTypeInspection */
                        $children[$idx] = $this->validateArray($child, $definition["definitions"], $errors);
                }
                $value = $children;
            }
            // Field contains definitions for multiple sub items with fields each
            if(isset($definition["definitions"]) && is_array($value) && $definition["type"] == "single"){
                $value = $this->validateArray($value, $definition["definitions"], $errors);
            }
            // Field contains a single value and no sub fields
            elseif(!isset($definition["definitions"]) && !is_array($value)){
                $value = Filter::filter($value, $definition["filter"], $definition["filter-options"]);
            }

            // Empty ?
            if($value === null || $value === "") {
                if ($definition["required"])
                    $errors[$key] = $value;

                if ($definition["keep-empty"])
                    Collection::set($valid, $key, null);
            }
            else {
                Collection::set($valid, $key, $value);
            }
        }

        return $valid;
    }

    // ------------------------------------------------------------------------------------------------------

    protected function getCachePath(){
        return __DIR__."/../../cache/".APP_INSTANCE."/".$this->identity.".cache";
    }

    protected function getConfigPath(){
        $default = __DIR__."/../../instances/default/config/".$this->identity.".yml";
        $instance = __DIR__."/../../instances/".APP_INSTANCE."/config/".$this->identity.".yml";

        if(file_exists($instance))
            return $instance;

        return $default;
    }

    protected function getCached(){
        $cache_file  = $this->getCachePath();
        $config_file = $this->getConfigPath();

        $cacheAge = @filemtime($cache_file);
        $configAge = @filemtime($config_file);

        // No cache present
        if($cacheAge === false)
            return false;
        // Cache to old
        if($cacheAge < $configAge){
            unlink($cache_file);
            return false;
        }

        // Cache is ok
        $config = file_get_contents($cache_file);
        return unserialize($config);
    }

    protected function setCached($config){
        $cache_file  = $this->getCachePath();
        $config = serialize($config);
        file_put_contents($cache_file, $config);
    }

    protected function getYaml(){
        $config_file = $this->getConfigPath();
        $parser = new sfYamlParser();
        $content = file_get_contents($config_file);
        $parsed = $parser->parse($content);

        $definitions = Collection::get($parsed, "definitions", array());
        $parsed["definitions"] = $this->parseDefinitions($definitions);

        return $parsed;
    }

    protected function parseDefinitions($definitions){
        foreach($definitions as $key => &$definition){
            if(isset($definition["definitions"])) {
                $definition["type"]           = Collection::get($definition, "type", "single");
                $definition["definitions"]    = $this->parseDefinitions($definition["definitions"]);
            }
            $definition["filter"]             = Collection::get($definition, "filter", "Strip");
            $definition["filter-options"]     = Collection::get($definition, "filter-options", array());
            $definition["default"]            = Collection::get($definition, "default", null);
            $definition["required"]           = Collection::get($definition, "required", false);
            $definition["keep-empty"]         = Collection::get($definition, "keep-empty", true);
        }

        return $definitions;
    }
}