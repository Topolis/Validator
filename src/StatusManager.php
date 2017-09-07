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

class StatusManager {

    const ERROR     = -100;
    const INVALID   = -11;
    const SANITIZED = -2;
    const INFO      = -1;

    protected static $levels = [self::ERROR, self::INVALID, self::SANITIZED, self::INFO];

    protected $messages = [];
    protected $status = true;

    protected $path = [];

    public function __construct() {
    }

    public function resetPath(){
        $this->path = [];
    }
    public function setPath(array $path){
        $this->path = $path;
    }
    public function enterPath($path){
        $this->path[] = $path;
    }
    public function exitPath(){
        array_pop($this->path);
    }

    public function addMessage($level, $message, $value, $definition){

        if(!in_array($level, self::$levels))
            throw new Exception("Invalid error level '".$level."' sent");

        $message = [
            "level" => $level,
            "message" => $message,
            "value" => $value,
            "definition" => $definition
        ];

        $this->status = $this->status == true ? $level : min($this->status, $level);

        $this->messages[] = $message;
    }

    public function getMessages(){
        return $this->messages;
    }

    public function getStatus(){
        return $this->status;
    }

    public function getPath(){
        return $this->path;
    }

}