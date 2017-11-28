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
use Exception;
use Topolis\Validator\Schema\INode;
use Topolis\Validator\Schema\IValidator;
use Topolis\Validator\Schema\Node\Listing;
use Topolis\Validator\Schema\Node\Object;
use Topolis\Validator\Schema\Node\Value;
use Topolis\Validator\Schema\NodeFactory;

/**
 * Validator
 * Manager class for schema validations
 * @package Topolis
 * @subpackage Validator
 */

class Validator {

    /* @var INode $schema */
    protected $schema;
    /* @var IValidator $validator */
    protected $validator;
    /* @var StatusManager $errorhandler */
    protected $errorhandler;

    /**
     * @param $definitionfile
     * @param string|boolean $cachefolder folder path to cache folder
     */
    public function __construct($definitionfile, $cachefolder = false){

        $this->errorhandler = new StatusManager();

        $cachefile = $cachefolder ? $cachefolder."/".pathinfo($definitionfile, PATHINFO_FILENAME).".schema-cache" : false;

        $initCompleted = false;

        if($cachefile && !$initCompleted){
            $cached = $this->getCached($cachefile, $definitionfile);

            if($cached) {
                $this->schema    = $cached["schema"];
                $this->validator = $cached["validator"];

                $initCompleted = true;
            }
        }

        if(!$initCompleted) {

            $definition = $this->getYaml($definitionfile);

            $this->factory = new NodeFactory();
            $this->factory->registerClass(Listing::class);
            $this->factory->registerClass(Object::class);
            $this->factory->registerClass(Value::class);

            $this->schema = $this->factory->createNode($definition);
            $this->validator = $this->factory->createValidator($this->schema, $this->errorhandler);

            $initCompleted = true;
        }

         if($cachefile && $initCompleted) {
            $this->setCached($cachefile, [
                "schema"    => $this->schema,
                "validator" => $this->validator
            ]);
        }
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
    public function validate(array $values, $quiet = false){

        $this->errorhandler->reset();

        $valid = $this->validator->validate($values);
        $status = $this->errorhandler->getStatus();

        if($status <= StatusManager::INVALID){
            if(!$quiet)
                throw new Exception("Values for object invalid");
            else
                return false;
        }

        return $valid;
    }

    public function getMessages(){
        return $this->errorhandler->getMessages();
    }

    public function getStatus(){
        return $this->errorhandler->getStatus();
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
        return $parsed;
    }
}
