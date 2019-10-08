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
use Topolis\Validator\Schema\Conditional;
use Topolis\Validator\Schema\INode;
use Topolis\Validator\Schema\IValidator;
use Topolis\Validator\Schema\Node\Listing;
use Topolis\Validator\Schema\Node\Properties;
use Topolis\Validator\Schema\Node\Value;
use Topolis\Validator\Schema\NodeFactory;
use Topolis\Validator\Schema\Validators\ListingValidator;
use Topolis\Validator\Schema\Validators\PropertiesValidator;
use Topolis\Validator\Schema\Validators\ValueValidator;

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
    /* @var callable $parser */
    protected $parser;
    /* @var callable $defaultParser */
    protected static $defaultParser;

    /**
     * @param string|boolean $definitionfile
     * @param string|boolean $cachefolder folder path to cache folder
     * @throws Exception
     */
    public function __construct($definitionfile = false, $cachefolder = false){
        // For old legacy usage, the constructor can also directly trigger schema loading
        if($definitionfile)
            $this->loadSchema($definitionfile, $cachefolder);
    }

    // Schema loading and initialization -------------------------------------------------------------------------------

    /**
     * @throws Exception
     */
    public function loadSchema($definitionfile, $cachefolder = false){

        $cachefile = $cachefolder ? $cachefolder."/".pathinfo($definitionfile, PATHINFO_FILENAME).".schema-cache" : false;

        $initCompleted = false;

        if($cachefile && !$initCompleted){
            $cached = $this->getCached($cachefile, $definitionfile);

            if($cached) {
                $this->errorhandler = $cached["errorhandler"];
                $this->schema    = $cached["schema"];
                $this->validator = $cached["validator"];

                $initCompleted = true;
            }
        }

        if(!$initCompleted) {

            $this->initParser();
            $definition = $this->getYaml($definitionfile);

            $factory = new NodeFactory();
            $factory->registerClass(Listing::class);
            $factory->registerClass(Properties::class);
            $factory->registerClass(Value::class);

            $this->errorhandler = new StatusManager();
            $this->schema = $factory->createNode($definition);
            $this->validator = $factory->createValidator($this->schema, $this->errorhandler);

            $initCompleted = true;
        }

        if($cachefile && $initCompleted) {
            $this->setCached($cachefile, [
                "errorhandler" => $this->errorhandler,
                "schema"    => $this->schema,
                "validator" => $this->validator,
            ]);
        }
    }

    // Parser Initialization -------------------------------------------------------------------------------------------

    /**
     * @param $parser
     * @throws Exception
     */
    public static function setDefaultParser(callable $parser){
        self::$defaultParser = $parser;
    }

    public function setParser(callable $parser){
        $this->parser = $parser;
    }

    protected function initParser(){
        if(!$this->parser)
            $this->parser = self::$defaultParser;

        if(!$this->parser)
            $this->parser = [$this, 'defaultParser'];
    }

    protected function defaultParser($input){
        if(is_array($input))
            return $input;

        return \Symfony\Component\Yaml\Yaml::parseFile($input);
    }

    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Validate $array of values according to field definitions
     * @param array $values values to validate
     * @param bool $quiet throw exception on error or just quietly return false
     * @return array|bool false (if $quiet and invalid values) or validated $array including defaults
     * @throws Exception
     */
    public function validate(array $values, $quiet = false){

        if(!$this->validator)
            throw new Exception('No schema has been loaded');

        $this->errorhandler->reset();

        $valid = $this->validator->validate($values);
        $status = $this->errorhandler->getStatus();

        if($status <= StatusManager::INVALID){
            if(!$quiet)
                throw new Exception('Values for object invalid');
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
        $result =  unserialize($definition, ['allowed_classes' => [
            Listing::class,
            Properties::class,
            Value::class,
            ListingValidator::class,
            PropertiesValidator::class,
            ValueValidator::class,
            Conditional::class,
            NodeFactory::class,
            StatusManager::class,
        ]]);
        return $result;
    }

    protected function setCached($cachefile, $definition){
        $definition = serialize($definition);
        file_put_contents($cachefile, $definition);
    }

    protected function getYaml($schemafile){
        return \call_user_func($this->parser, $schemafile);
    }
}
