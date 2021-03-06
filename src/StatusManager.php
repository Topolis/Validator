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

    const VALID     = 1;

    protected static $levels = [
        self::ERROR => "error",
        self::INVALID => "invalid",
        self::SANITIZED => "sanitized",
        self::INFO => "info"
    ];

    protected $messages = [];
    protected $status = self::VALID;

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
        array_push($this->path, $path);
    }
    public function exitPath(){
        array_pop($this->path);
    }

    public function addMessage($level, $message, $value, $definition){

        // We now allow custom error codes!
        //if(!in_array($level, array_keys(self::$levels)))
        //    throw new Exception("Invalid error level '".$level."' sent");

        $message = [
            "level" => $level,
            "message" => $message,
            "value" => $value,
            "definition" => $definition,
            "path" => $this->path
        ];

        $this->status = min($this->status, $level);

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

    public function reset() {
        $this->messages = [];
        $this->status = self::VALID;
        $this->path = [];
    }

    public static function levelToString($level) {

        if(!in_array($level, array_keys(self::$levels)))
            throw new Exception("Invalid error level '".$level."' sent");

        return self::$levels[$level];
    }

}
