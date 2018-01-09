<?php

namespace Topolis\Validator\Schema\Node;

use Exception;
use Topolis\Validator\Schema\Conditional;
use Topolis\Validator\Schema\INode;
use Topolis\Validator\Schema\NodeFactory;
use Topolis\Validator\Schema\Validators\PropertiesValidator;

class Properties implements INode {

    public $name;

    protected $type;

    /* @var INode[] $properties */
    protected $properties = [];

    /* @var Conditional[] $conditionals */
    protected $conditionals = [];

    /* @var Value $index */
    protected $index;

    protected $default;

    protected $required;

    /* @var NodeFactory $factory */
    protected $factory;

    // Available definition tyles
    const TYPE_SINGLE = "single";
    const TYPE_MULTIPLE = "multiple";
    const TYPE_DEFAULT = self::TYPE_SINGLE;

    /**
     * Schema constructor.
     * @param array $data
     * @param NodeFactory $factory
     */
    public function __construct(array $data, NodeFactory $factory) {
        $this->factory = $factory;
        $this->import($data);
    }

    public static function detect(array $schema) {
        return is_array($schema) and isset($schema["properties"]);
    }

    public static function validator() {
        return PropertiesValidator::class;
    }

    /**
     * @param array $data
     * @throws Exception
     */
    public function import(array $data){

        $data = $data + [
            "type" => self::TYPE_DEFAULT,
            "conditionals" => [],
            "default" => null,
            "required" => false,
            "filter" => "Passthrough",
            "options" => [],
            "properties" => []
        ];

        $this->type = $data["type"];
        $this->default = $data["default"];
        $this->required = $data["required"];

        if($this->type == self::TYPE_MULTIPLE){

            $field = [
                "filter" => $data["filter"],
                "options" => $data["options"]
            ];
            $this->index = $this->factory->createNode($field);

            if(!$this->index instanceof Value)
                throw new Exception("Key for property must be of type value");
        }

        foreach($data["conditionals"] as $conditional){
            $this->conditionals[] = new Conditional($conditional, $data, $this->factory);
        }

        foreach($data["properties"] as $key => $property){
            $this->properties[$key] = $this->factory->createNode($property);
        }

    }

    /**
     * @return array
     */
    public function export() {
        $export = [
            "type" => $this->getType(),
            "conditionals" => [],
            "properties" => [],
            "default" => $this->getDefault(),
            "required" => $this->getRequired()
        ];

        foreach($this->conditionals as $conditional)
            $export["conditionals"][] = $conditional->export();

        foreach($this->properties as $property)
            $export["properties"][] = $property->export();

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
     * @return Value|null
     */
    public function getIndex(){
        return $this->index ? $this->index : null;
    }

    /**
     * @return INode[]
     */
    public function getProperties(){
        return $this->properties;
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
     * @param INode $schema
     */
    public function merge(INode $schema){

        /* @var Object $schema */

        $this->default  = $schema->getDefault()  ? $schema->getDefault()  : $this->default;
        $this->required = $schema->getRequired() ? $schema->getRequired() : $this->required;
        $this->type     = $schema->getType()     ? $schema->getType()     : $this->type;
        $this->index    = $schema->getIndex()    ? $schema->getIndex()    : $this->index;

        if($this->type != self::TYPE_MULTIPLE){
            $this->index = null;
        }

        // Definitions
        foreach ($schema->getProperties() as $key => $property){
            if(isset($this->properties[$key]) && get_class($this->properties[$key]) == get_class($property))
                $this->properties[$key]->merge($property);
            else
                $this->properties[$key] = $property;
        }

        // Conditionals (Cant be smartly merged as we dont have a key for them (Might be a CR?)
        $this->conditionals = array_merge($this->conditionals, $schema->getConditionals());

    }

}