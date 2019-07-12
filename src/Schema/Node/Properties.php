<?php

namespace Topolis\Validator\Schema\Node;

use Exception;
use Topolis\Validator\Schema\Conditional;
use Topolis\Validator\Schema\INode;
use Topolis\Validator\Schema\NodeFactory;
use Topolis\Validator\Schema\Validators\PropertiesValidator;

class Properties extends BaseNode implements INode {

    public $name;

    protected $type;
    /* @var INode[] $properties */
    protected $properties = [];
    /* @var Value $index */
    protected $index;

    // Available definition tyles
    const TYPE_SINGLE = "single";
    const TYPE_MULTIPLE = "multiple";
    const TYPE_DEFAULT = self::TYPE_SINGLE;

    public static function detect(array $schema) {
        return \is_array($schema) and isset($schema["properties"]);
    }

    public static function validator() {
        return PropertiesValidator::class;
    }

    /**
     * @param array $data
     * @throws Exception
     */
    public function import(array $data){
        parent::import($data);

        $data += [
            "type" => self::TYPE_DEFAULT,
            "filter" => "Passthrough",
            "options" => [],
            "properties" => []
        ];

        $this->type = $data["type"];

        if($this->type === self::TYPE_MULTIPLE){

            $field = [
                "filter" => $data["filter"],
                "options" => $data["options"]
            ];
            $this->index = $this->factory->createNode($field);

            if(!$this->index instanceof Value)
                throw new Exception("Key for property must be of type value");
        }

        foreach($data["properties"] as $key => $property){
            $this->properties[$key] = $this->factory->createNode($property);
        }
    }

    /**
     * @return array
     */
    public function export() {
        $export = parent::export();
        $export += [
            "type" => $this->getType(),
            "properties" => [],
        ];

        foreach($this->properties as $property)
            $export["properties"][] = $property->export();

        if($this->type === self::TYPE_MULTIPLE){
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
     * @param INode $schema
     */
    public function merge(INode $schema){

        /* @var Object $schema */

        $this->default  = $schema->getDefault()  ? $schema->getDefault()  : $this->default;
        $this->required = $schema->getRequired() ? $schema->getRequired() : $this->required;
        $this->type     = $schema->getType()     ? $schema->getType()     : $this->type;
        $this->index    = $schema->getIndex()    ? $schema->getIndex()    : $this->index;

        if($this->type !== self::TYPE_MULTIPLE){
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

        $this->errors += $schema->getErrors();
    }

}