<?php

namespace Topolis\Validator\Schema;

use Topolis\Validator\Traits\MagicGetSet;

class Schema {

    public $name;

    protected $type;

    /* @var Field[]|Schema[] $definitions */
    protected $definitions = [];

    /* @var Conditional[] $conditionals */
    protected $conditionals = [];

    /* @var Field $index */
    protected $index;

    protected $default;

    protected $required;

    // Available definition tyles
    const TYPE_SINGLE = "single";
    const TYPE_MULTIPLE = "multiple";
    const TYPE_DEFAULT = self::TYPE_SINGLE;

    /**
     * Schema constructor.
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
            "type" => self::TYPE_DEFAULT,
            "conditionals" => [],
            "definitions" => [],
            "default" => null,
            "required" => false,
            "filter" => "Passthrough",
            "options" => []
        ];

        $this->type = $data["type"];
        $this->default = $data["default"];
        $this->required = $data["required"];

        if($this->type == self::TYPE_MULTIPLE){

            $field = [
                "filter" => $data["filter"],
                "options" => $data["options"]
            ];
            $this->index = new Field($field);
        }


        foreach($data["conditionals"] as $conditional){
            $this->conditionals[] = new Conditional($conditional, get_class($this));
        }

        foreach($data["definitions"] as $field => $definition){

            if(isset($definition["definitions"]))
                $this->definitions[$field] = new Schema($definition);
            else
                $this->definitions[$field] = new Field($definition);
        }

    }

    /**
     * @return array
     */
    public function export() {
        $export = [
            "type" => $this->getType(),
            "conditionals" => [],
            "definitions" => [],
            "default" => $this->getDefault(),
            "required" => $this->getRequired()
        ];

        foreach($this->conditionals as $conditional)
            $export["conditionals"][] = $conditional->export();

        foreach($this->definitions as $definition)
            $export["definitions"][] = $definition->export();

        if($this->type == self::TYPE_MULTIPLE){
            $export["filter"] = $this->index->getFilter();
            $export["options"] = $this->index->getOptions();
        }

        return $export;
    }

    // ----------------------------------------------------------------

    /**
     * @return string
     */
    public function getType(){
        return $this->type ? $this->type : self::TYPE_DEFAULT;
    }

    /**
     * @return Field|null
     */
    public function getIndex(){
        return $this->index ? $this->index : null;
    }

    /**
     * @return Field[]|Schema[]
     */
    public function getDefinitions(){
        return $this->definitions;
    }

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
     * @param Schema $schema
     */
    public function merge(Schema $schema){

        $this->default  = $schema->getDefault()  ? $schema->getDefault()  : $this->default;
        $this->required = $schema->getRequired() ? $schema->getRequired() : $this->required;
        $this->type     = $schema->getType()     ? $schema->getType()     : $this->type;
        $this->index    = $schema->getIndex()    ? $schema->getIndex()    : $this->index;

        if($this->type != self::TYPE_MULTIPLE){
            $this->index = null;
        }

        // Definitions
        foreach ($schema->getDefinitions() as $key => $definition){
            if(isset($this->definitions[$key]) && get_class($this->definitions[$key]) == get_class($definition))
                $this->definitions[$key]->merge($definition);
            else
                $this->definitions[$key] = $definition;
        }

        // Conditionals (Cant be smartly merged as we dont have a key for them (Might be a CR?)
        $this->conditionals = array_merge($this->conditionals, $schema->getConditionals());

    }

}