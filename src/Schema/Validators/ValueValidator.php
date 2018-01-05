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

class ValueValidator implements IValidator {

    /* @var Value $node */
    protected $node;
    protected $statusManager;

    public function __construct(INode $node, StatusManager $statusManager, NodeFactory $factory) {
        $this->statusManager = $statusManager;
        $this->node = $node;
    }

    /**
     * @param $value
     * @param $data
     * @return mixed
     * @throws ValidatorException
     */
    public function validate($value, $data = null){

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
            $this->statusManager->addMessage(StatusManager::INVALID, "Invalid - Invalid value found", $original, $this->node);
            return null;
        }

        // Result sanitized
        if($value != $original){
            $this->statusManager->addMessage(StatusManager::SANITIZED, "Sanitized - value was sanitized", $original, $this->node);
        }

        return $value;
    }

}