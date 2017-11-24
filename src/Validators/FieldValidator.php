<?php

namespace Topolis\Validator\Validators;

use Topolis\Filter\Filter;
use Topolis\Filter\IFilterType;
use Topolis\Validator\StatusManager;
use Topolis\Validator\Schema\Value;
use Topolis\Validator\ValidatorException;

class FieldValidator {

    /* @var \Topolis\Validator\Schema\Value $definition */
    protected $definition;

    protected $statusHandler;

    public function __construct(Value $definition, StatusManager $statusHandler) {
        $this->statusHandler = $statusHandler;
        $this->definition = $definition;
    }

    /**
     * @param $value
     * @param $data
     * @return mixed
     * @throws ValidatorException
     */
    public function validate($value, $data){

        $conditionals = $this->definition->getConditionals();

        // If we need to change our definition because of conditionals, we need to preserve the original
        $definition = $conditionals ? clone $this->definition : $this->definition;

        foreach($conditionals as $conditional){
            $applicable = $conditional->evaluate($data);

            if($applicable)
                $definition->merge($conditional->getDefinition());
        }

        // Validate a value
        $original = $value;

        if($definition->getStrict())
            $value = Filter::validate($value, $definition->getFilter(), $definition->getOptions());
        else
            $value = Filter::filter($value, $definition->getFilter(), $definition->getOptions());

        // Result invalid
        if($value === false && $original !== false){
            $this->statusHandler->addMessage(StatusManager::INVALID, "Invalid - Invalid value found", $original, $this->definition);
            return null;
        }

        // Result sanitized
        if($value != $original){
            $this->statusHandler->addMessage(StatusManager::SANITIZED, "Sanitized - value was sanitized", $original, $this->definition);
        }

        return $value;
    }

}