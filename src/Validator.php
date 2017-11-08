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
use Exception;
use Topolis\Validator\Schema\Schema;
use Topolis\Validator\Validators\SchemaValidator;

/**
 * Validator
 * Manager class for schema validations
 * @package Topolis
 * @subpackage Validator
 */

class Validator {

    protected $identity;
    protected $definition;

    /**
     * @param string $schemafile             file path to yaml schema file
     * @param string|boolean $cachefolder    folder path to cache folder
     */
    public function __construct($definitionfile, $cachefolder = false){

        $definition = null;

        $cachefile = $cachefolder ? $cachefolder."/".pathinfo($definitionfile, PATHINFO_FILENAME).".definition-cache" : false;

        if($cachefile){
            $definition = $this->getCached($cachefile, $definitionfile);
        }

        if(!$definition){
            $definition = $this->getYaml($definitionfile);

            if($cachefile)
                $this->setCached($cachefile, $definition);
        }

        $this->identity = pathinfo($definitionfile, PATHINFO_FILENAME);
        $this->definition = $definition;
    }

    // ------------------------------------------------------------------------------------------------------

    /**
     * Validate $array of values according to field definitions
     * @param array $values values to validate
     * @param bool $quiet throw exception on error or just quietly return false
     * @param array $errors
     * @return array|bool false (if $quiet and invalid values) or validated $array including defaults
     * @throws Exception
     */
    public function validate(array $values, $quiet = false, &$errors = array()){

        $errorHandler = new StatusManager();
        $validator = new SchemaValidator($this->definition, $errorHandler);

        $valid = $validator->validate($values);
        $errors = $errorHandler->getMessages();

        if($errorHandler->getStatus() <= StatusManager::INVALID){
            if(!$quiet)
                throw new Exception("Values for ".$this->identity." object invalid");
            else
                return false;
        }

        return $valid;
    }

    // ------------------------------------------------------------------------------------------------------

    protected function getCached($cachefile, $definitionfile){

        $cacheAge = @filemtime($cachefile);
        $configAge = @filemtime($definitionfile);

        // No cache present
        if($cacheAge === false)
            return false;
        // Cache to old
        if($cacheAge < $configAge){
            unlink($cachefile);
            return false;
        }

        // Cache is ok
        $definition = file_get_contents($cachefile);
        return unserialize($definition);
    }

    protected function setCached($cachefile, $definition){
        $definition = serialize($definition);
        file_put_contents($cachefile, $definition);
    }

    protected function getYaml($schemafile){
        $content = file_get_contents($schemafile);
        $parsed = Yaml::parse($content);

        $definition = new Schema($parsed);

        return $definition;
    }
}
