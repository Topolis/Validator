<?php

namespace Topolis\Validator\Schema\Node;

use Topolis\Validator\Schema\Conditional;
use Topolis\Validator\Schema\INode;
use Topolis\Validator\Schema\NodeFactory;

abstract class BaseNode implements INode {

    /* @var NodeFactory $factory */
    protected $factory;

    protected $default = null;
    protected $required = false;
    protected $errors = [];
    protected $conditionals = [];


    /**
     * Field constructor.
     * @param array $data
     * @param NodeFactory $factory
     */
    public function __construct(array $data, NodeFactory $factory) {
        $this->factory = $factory;
        $this->import($data);
    }

    public function import(array $data){

        $data += [
            "default" => null,
            "required" => false,
            "conditionals" => [],
            "errors" => [],
        ];

        $this->default = $data["default"];
        $this->required = $data["required"];

        foreach($data["conditionals"] as $conditional){
            $this->conditionals[] = new Conditional($conditional, $data, $this->factory);
        }

        foreach($data["errors"] as $code => $config){
            $this->errors[(int)$code] = $config;
        }
    }

    /**
     * @return array
     */
    public function export() {
        $export = [
            "default" => $this->getDefault(),
            "required" => $this->getRequired(),
        ];

        foreach($this->conditionals as $conditional)
            $export["conditionals"][] = $conditional->export();

        foreach($this->errors as $code => $config)
            $export["errors"][$code] = $config;

        return $export;
    }

    /**
     * @param int $level
     * @return int
     */
    public function getErrorCode($level){
        return $this->errors[$level]['code'] ?? $level;
    }

    /**
     * @param int $level
     * @param string $default
     * @return string
     */
    public function getErrorMessage($level, $default){
        return $this->errors[$level]['message'] ?? $default;
    }

    // ----------------------------------------------------------------

    /**
     * @return Conditional[]
     */
    public function getConditionals(){
        return $this->conditionals;
    }

    /**
     * @return mixed
     */
    public function getDefault(){
        return $this->default;
    }

    /**
     * @return mixed
     */
    public function getRequired(){
        return $this->required;
    }

    /**
     * @return Conditional[]
     */
    public function getErrors(){
        return $this->errors;
    }

}