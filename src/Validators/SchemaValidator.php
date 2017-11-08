<?php

namespace Topolis\Validator\Validators;

use Topolis\Filter\Filter;
use Topolis\FunctionLibrary\Collection;
use Topolis\Validator\StatusManager;
use Topolis\Validator\Schema\Field;
use Topolis\Validator\Schema\Schema;
use Topolis\Validator\ValidatorException;

class SchemaValidator {

    protected static $path = [];

    /* @var \Topolis\Validator\Schema\Schema $definition */
    protected $definition;
    protected $statusManager;

    protected $data; // The full object to be validated. Needed for Conditionals

    public function __construct(Schema $definition, StatusManager $statusManager) {
        $this->statusManager = $statusManager;
        $this->definition = $definition;
    }

    /**
     * @param $value
     * @param $data
     * @return mixed
     * @throws ValidatorException
     */
    public function validate($values, $data = null){

        if(!$data)
            $data = $values;

        $this->data =& $data;

        $conditionals = $this->definition->getConditionals();

        // If we need to change our definition because of conditionals, we need to preserve the original
        $definition = $conditionals ? clone $this->definition : $this->definition;

        foreach($conditionals as $conditional){
            $applicable = $conditional->evaluate($data);

            if($applicable)
                $definition->merge($conditional->getDefinition());
        }

        // Type if multiple
        if($definition->getType() == Schema::TYPE_MULTIPLE){
            $children = array();
            foreach($values as $idx => $child) {

                $this->statusManager->enterPath($idx);

                $indexValidator = new FieldValidator($definition->getIndex(), $this->statusManager);

                $idx = $indexValidator->validate($idx, $this->data);

                if($idx !== null && is_array($child)){
                    $children[$idx] = $this->applyDefinitions($child, $definition);
                }

                $this->statusManager->exitPath();
            }
            return $children;
        }

        // Type is single
        if($definition->getType() == Schema::TYPE_SINGLE) {
            return $this->applyDefinitions($values, $definition);
        }

        // Type is invalid
        $this->statusManager->addMessage(
            StatusManager::ERROR,
            "Invalid schema type",
            $definition->getType(),
            $definition
        );
        return null;
    }

    /**
     * @param array $values
     * @param Schema|Field $definition
     * @return array
     */
    protected function applyDefinitions($values, $definition){
        // Validate a value
        $valid = array();

        $surpusKeys = array_keys(array_diff_key($values, $definition->getDefinitions()));
        if($surpusKeys){
            $this->statusManager->addMessage(
                StatusManager::SANITIZED,
                "Invalid - Additional keys present (".implode(",",$surpusKeys).")",
                $values,
                $definition
            );
        }

        foreach($definition->getDefinitions() as $key => $subdefinition){

            $value = Collection::get($values, $key, $subdefinition->getDefault());
            $this->statusManager->enterPath($key);

            try {
                // Sub definition is a Schema
                if ($subdefinition instanceof Schema) {
                    $validator = new SchemaValidator($subdefinition, $this->statusManager);
                    $value = $value ? $validator->validate($value, $this->data) : [];

                    if ($subdefinition->getRequired() && $value == [])
                        throw new ValidatorException("Required fields are missing");

                    if($value)
                        Collection::set($valid, $key, $value);
                }
                // Subdefinition is a Field
                elseif ($subdefinition instanceof Field) {
                    $validator = new FieldValidator($subdefinition, $this->statusManager);
                    $value = $validator->validate($value, $this->data);

                    // Empty value as specified in "remove" - We always treat "" or null as empty
                    $remove = is_array($subdefinition->getRemove()) && in_array($value, $subdefinition->getRemove());

                    if ($subdefinition->getRequired() && ($value == null || $remove))
                        throw new ValidatorException("Required field is missing");

                    if(!$remove)
                        Collection::set($valid, $key, $value);
                }

            } catch (ValidatorException $e) {
                // Error reporting
                $this->statusManager->addMessage(
                    StatusManager::INVALID,
                    $e->getMessage(),
                    $value,
                    $definition
                );
            }

            $this->statusManager->exitPath();
        }

        // Sort result by order given in schema
        $order = array_keys($definition->getDefinitions());
        $valid = array_replace(array_intersect_key(array_flip($order), $valid), $valid);

        return $valid;
    }

}
