<?php

namespace Topolis\Validator\Schema;

use Topolis\Validator\Traits\MagicGetSet;

class Field {

    protected $filter = self::FILTER_DEFAULT;
    protected $options = [];
    protected $default = null;
    protected $required = false;
    protected $remove = ['', null];
    protected $strict = false;
    protected $conditionals = [];

    const FILTER_DEFAULT = "Passthrough";

    /**
     * Field constructor.
     * @param array $data
     */
    public function __construct(array $data) {
        $this->import($data);
    }

    /**
     * @param array $data
     */
    public function import(array $data){

        $data = $data + [
                "filter" => self::FILTER_DEFAULT,
                "options" => [],
                "default" => null,
                "required" => false,
                "remove" => false,
                "strict" => false,
                "conditionals" => []
            ];

        $this->filter = $data["filter"];
        $this->options = $data["options"];
        $this->default = $data["default"];
        $this->required = $data["required"];
        $this->remove = $data["remove"];
        $this->strict = $data["strict"];

        foreach($data["conditionals"] as $conditional){
            $this->conditionals[] = new Conditional($conditional, get_class($this));
        }
    }

    /**
     * @return array
     */
    public function export() {
        $export = [
            "filter" => $this->getFilter(),
            "options" => $this->getOptions(),
            "default" => $this->getDefault(),
            "required" => $this->getRequired(),
            "remove" => $this->getRemove(),
            "strict" => $this->getStrict()
        ];

        foreach($this->conditionals as $conditional)
            $export["conditionals"][] = $conditional->export();

        return $export;
    }

    // ----------------------------------------------------------------

    /**
     * @return string
     */
    public function getFilter(){
        return $this->filter;
    }

    /**
     * @return array
     */
    public function getOptions(){
        return $this->options;
    }

    /**
     * @return null
     */
    public function getDefault(){
        return $this->default;
    }

    /**
     * @return bool
     */
    public function getRequired(){
        return $this->required;
    }

    /**
     * @return bool|array
     */
    public function getRemove(){
        return $this->remove;
    }

    /**
     * @return bool
     */
    public function getStrict(){
        return $this->strict;
    }

    /**
     * @return Conditional[]
     */
    public function getConditionals(){
        return $this->conditionals;
    }

    /**
     * @param Field $definition
     */
    public function merge(Field $definition){
        $this->filter = $definition->getFilter() !== self::FILTER_DEFAULT ? $definition->getFilter() : $this->filter;
        $this->options = array_merge($this->options, $definition->getOptions());
        $this->default = $definition->getDefault() ? $definition->getDefault() : $this->default;
        $this->required = $definition->getRequired() ? $definition->getRequired() : $this->required;
        $this->remove = $definition->getRemove() ? $definition->getRemove() : $this->remove;
    }

}