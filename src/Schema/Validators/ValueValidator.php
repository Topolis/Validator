<?php

namespace Topolis\Validator\Schema\Validators;

// The Topolis/Filter libraray defines the type of values per default as "any". This allows single values and arrays/trees of values.
// The Validator is more restrictive and changes this behaviour to a default of "single".
use Topolis\Validator\Overrides\Filter;

use Topolis\Validator\Schema\Conditional;
use Topolis\Validator\Schema\INode;
use Topolis\Validator\Schema\IValidator;
use Topolis\Validator\Schema\Node\Value;
use Topolis\Validator\Schema\NodeFactory;
use Topolis\Validator\StatusManager;
use Topolis\Validator\ValidatorException;

class ValueValidator extends BaseValidator implements IValidator {

    /**
     * @param $value
     * @param $data
     * @return mixed
     * @throws \Exception
     */
    public function validate($value, $data = null){

        /* @var Value $node */
        $node = $this->node;

        // Apply conditionals
        /* @var Conditional $conditional */
        foreach($node->getConditionals() as $conditional){
            if($conditional->evaluate($data, $this->statusManager))
                $node = $conditional->getNode();
        }

        // Validate a value
        $original = $value;

        if($node->getStrict())
            $value = Filter::validate($value, $node->getFilter(), $node->getOptions());
        else
            $value = Filter::filter($value, $node->getFilter(), $node->getOptions());

        // Result invalid
        if($value === false && $original !== false){
            $this->addStatusMessage(StatusManager::INVALID, "Invalid - Invalid value found", $original);
            return null;
        }

        // Result sanitized
        if($value != $original){
            $this->addStatusMessage(StatusManager::SANITIZED, "Sanitized - value was sanitized", $original);
        }

        return $value;
    }

}