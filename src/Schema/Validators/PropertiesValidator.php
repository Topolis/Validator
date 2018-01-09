<?php

namespace Topolis\Validator\Schema\Validators;

use Topolis\Validator\Schema\Conditional;
use Topolis\Validator\Schema\INode;
use Topolis\Validator\Schema\IValidator;
use Topolis\Validator\Schema\Node\Properties;
use Topolis\Validator\Schema\NodeFactory;
use Topolis\Validator\StatusManager;
use Topolis\Validator\ValidatorException;

class PropertiesValidator implements IValidator  {

    protected static $path = [];

    /* @var Object $node */
    protected $node;
    protected $statusManager;
    protected $factory;

    protected $indexValidator;
    protected $propertyValidator = [];

    protected $data; // The full object to be validated. Needed for Conditionals

    public function __construct(INode $node, StatusManager $statusManager, NodeFactory $factory) {
        $this->statusManager = $statusManager;
        $this->factory = $factory;

        /* @var \Topolis\Validator\Schema\Node\Properties $node */
        $this->node = $node;
    }

    /**
     * @param $values
     * @param $data
     * @return mixed
     */
    public function validate($values, $data = null){

        if(!$data)
            $data = $values;

        $this->data =& $data;

        $node = $this->node;

        // Apply conditionals
        /* @var Conditional $conditional */
        foreach($node->getConditionals() as $conditional){
            if($conditional->evaluate($data, $this->statusManager))
                $node = $conditional->getNode();
        }

        // Index Validator for type multiple
        if($node->getType() == Properties::TYPE_MULTIPLE)
            $this->indexValidator = new ValueValidator($node->getIndex(), $this->statusManager, $this->factory);

        // Property validators
        foreach($node->getProperties() as $key => $subnode)
            $this->propertyValidator[$key] = $this->factory->createValidator($subnode, $this->statusManager);

        // Type if multiple
        if($node->getType() == Properties::TYPE_MULTIPLE){

            $children = array();
            foreach($values as $idx => $child) {

                $this->statusManager->enterPath($idx);

                $idx = $this->indexValidator->validate($idx, $this->data);

                if($idx !== null && is_array($child)){
                    $children[$idx] = $this->applyDefinitions($child, $node);
                }

                $this->statusManager->exitPath();
            }
            return $children;
        }

        // Type is single
        if($node->getType() == Properties::TYPE_SINGLE) {
            return $this->applyDefinitions($values, $node);
        }

        // Type is invalid
        $this->statusManager->addMessage(
            StatusManager::ERROR,
            "Invalid schema type",
            $node->getType(),
            $this->node
        );
        return null;
    }

    /**
     * @param array $values
     * @param \Topolis\Validator\Schema\Node\Properties $node
     * @return array
     */
    protected function applyDefinitions($values, $node){
        // Validate a value
        $valid = array();

        if(is_array($values)){
            $surpusKeys = array_keys(array_diff_key($values, $node->getProperties()));
            if($surpusKeys){
                $this->statusManager->addMessage(
                    StatusManager::SANITIZED,
                    "Sanitized - Additional keys present (".implode(",",$surpusKeys).")",
                    $values,
                    $node
                );
            }
        }

        /* @var INode $subnode */
        foreach($node->getProperties() as $key => $subnode){

            $this->statusManager->enterPath($key);
            $value = null;
            
            try {

                // Propery does not exist and no default specified
                if(!isset($values[$key]) && $subnode->getDefault() === null){
                    if ($subnode->getRequired())
                        throw new ValidatorException("Invalid - Required property is missing");
                }

                // Property exists
                else {
                    $value = isset($values[$key]) ? $values[$key] : $subnode->getDefault();

                    $value = $this->propertyValidator[$key]->validate($value, $this->data);

                    if ($subnode->getRequired() && $value === null)
                        throw new ValidatorException("Invalid - Required field is empty");

                    // FIXME: the "Remove empty properties" feature has been removed and needs to be reimplemented in a cleaner way if realy needed
                    $valid[$key] = $value;
                }

            } catch (ValidatorException $e) {
                // Error reporting
                $this->statusManager->addMessage(
                    StatusManager::INVALID,
                    $e->getMessage(),
                    $value,
                    $subnode
                );
            }

            $this->statusManager->exitPath();
        }

        // Sort result by order given in schema
        $order = array_keys($node->getProperties());
        $valid = array_replace(array_intersect_key(array_flip($order), $valid), $valid);

        return $valid;
    }

}
