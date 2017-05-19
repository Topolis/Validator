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

use Symfony\Component\Yaml\Yaml;
use Topolis\FunctionLibrary\Collection;
use Topolis\Filter\Filter;
use Exception;

/**
 * Validator
 * Manager class for schema validations
 * @package Topolis
 * @subpackage Validator
 */

class Validator {

    protected $schema = [];

    protected $definitionParser = null;
    protected $valueProcessor = null;

    /**
     * @param string $schemafile             file path to yaml schema file
     * @param string|boolean $cachefolder    folder path to cache folder
     */
    public function __construct($schemafile, $cachefolder = false){

        $this->definitionParser = new DefinitionParser();
        $this->valueProcessor = new ValueProcessor();

        $cachefile = $cachefolder ? $cachefolder."/".pathinfo($schemafile, PATHINFO_FILENAME).".schema-cache" : false;

        if($cachefile){
            $this->schema = $this->getCached($cachefile, $schemafile);
        }

        if(!$this->schema){
            $this->schema = $this->getYaml($schemafile);

            if($cachefile)
                $this->setCached($cachefile, $this->schema);
        }

        $this->identity = Collection::get($this->schema, "name", pathinfo($schemafile, PATHINFO_FILENAME));

        $this->valueProcessor->setDefinitions($this->schema["definitions"]);
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
        $valid = $this->valueProcessor->validate($values);
        $errors = $this->valueProcessor->getErrors();

        if($errors){
            if(!$quiet)
                throw new Exception("Values for ".$this->identity." object invalid");
            else
                return false;
        }

        return $valid;
    }

    // ------------------------------------------------------------------------------------------------------

    protected function getCached($cachefile, $schemafile){

        $cacheAge = @filemtime($cachefile);
        $configAge = @filemtime($schemafile);

        // No cache present
        if($cacheAge === false)
            return false;
        // Cache to old
        if($cacheAge < $configAge){
            unlink($cachefile);
            return false;
        }

        // Cache is ok
        $config = file_get_contents($cachefile);
        return unserialize($config);
    }

    protected function setCached($cachefile, $schema){
        $schema = serialize($schema);
        file_put_contents($cachefile, $schema);
    }

    protected function getYaml($schemafile){
        $content = file_get_contents($schemafile);
        $parsed = Yaml::parse($content);

        $definitions = Collection::get($parsed, "definitions", array());
        $parsed["definitions"] = $this->definitionParser->parse($definitions);

        return $parsed;
    }
}