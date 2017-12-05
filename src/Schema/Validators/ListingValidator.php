<?php

namespace Topolis\Validator\Schema\Validators;

use Topolis\FunctionLibrary\Collection;
use Topolis\Validator\Schema\Conditional;
use Topolis\Validator\Schema\INode;
use Topolis\Validator\Schema\IValidator;
use Topolis\Validator\Schema\Node\Listing;
use Topolis\Validator\Schema\Node\Value;
use Topolis\Validator\Schema\NodeFactory;
use Topolis\Validator\StatusManager;
use Topolis\Validator\ValidatorException;

class ListingValidator implements IValidator {

    protected static $path = [];

    /* @var Listing $definition */
    protected $node;
    /* @var StatusManager $definition */
    protected $statusManager;

    /* @var Value $keyValidator */
    protected $keyValidator;
    /* @var INode $valuaValidator */
    protected $valueValidator;

    protected $data; // The full object to be validated. Needed for Conditionals

    public function __construct(INode $node, StatusManager $statusManager, NodeFactory $factory) {
        $this->statusManager = $statusManager;
        /* @var Listing $node */
        $this->node = $node;

        $this->keyValidator = $factory->createValidator($node->getKey(), $this->statusManager);
        $this->valueValidator = $factory->createValidator($node->getValue(), $this->statusManager);
    }

    /**
     * @param $values
     * @param $data
     * @return mixed
     */
    public function validate($values, $data = null){

        $valid = [];

        if(!$data)
            $data = $values;
        $this->data =& $data;

        $node = $this->node;

        // Apply conditionals
        /* @var Conditional $conditional */
        foreach($node->getConditionals() as $conditional){
            if($conditional->evaluate($data))
                $node = $conditional->getNode();
        }

        // Validate
        if(!is_array($values)){
            $this->statusManager->addMessage(StatusManager::INVALID, "Invalid - Invalid value found", $values, $this->node);
            return null;
        }

        foreach($values as $key => $value) {

            $this->statusManager->enterPath($key);

            $key = $this->keyValidator->validate($key, $this->data);
            if(!$key){
                $this->statusManager->addMessage(StatusManager::INVALID, "Invalid - Invalid listing key", $key, $this->node);
                return null;
            }

            $value = Collection::get($values, $key, $node->getValue()->getDefault());
            $value = $this->valueValidator->validate($value, $this->data);

            // Empty value as specified in "remove" - We always treat "" or null as empty
            $remove = is_array($node->getValue()->getRemove()) && in_array($value, $node->getValue()->getRemove());

            if ($node->getValue()->getRequired() && ($value == null || $remove))
                throw new ValidatorException("Required field is missing");

            if(!$remove)
                $valid[$key] = $value;

            $this->statusManager->exitPath();
        }

        if( $node->getMin() !== false && count($valid) < $node->getMin() ) {
            $this->statusManager->addMessage(StatusManager::INVALID, "Invalid - less than ".$node->getMin()." values in listing", $valid, $this->node);
            return null;
        }

        if( $node->getMax() !== false && count($valid) > $node->getMax() ) {
            $this->statusManager->addMessage(StatusManager::INVALID, "Invalid - more than ".$node->getMax()." values in listing", $valid, $this->node);
            return null;
        }

        return $valid;
    }

}
