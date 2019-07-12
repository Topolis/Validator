<?php

namespace Topolis\Validator\Schema\Validators;

use Topolis\Validator\Schema\Conditional;
use Topolis\Validator\Schema\INode;
use Topolis\Validator\Schema\IValidator;
use Topolis\Validator\Schema\Node\Properties;
use Topolis\Validator\Schema\NodeFactory;
use Topolis\Validator\StatusManager;
use Topolis\Validator\ValidatorException;

class PropertiesValidator extends BaseValidator implements IValidator  {

    protected $indexValidator;
    protected $propertyValidator = [];

    protected $data; // The full object to be validated. Needed for Conditionals

    /**
     * @param $values
     * @param $data
     * @return mixed
     * @throws \Exception
     */
    public function validate($values, $data = null){

        if(!$data)
            $data = $values;

        $this->data =& $data;

        /* @var Properties $node */
        $node = $this->node;

        // Apply conditionals
        /* @var Conditional $conditional */
        foreach($node->getConditionals() as $conditional){
            if($conditional->evaluate($data, $this->statusManager))
                $node = $conditional->getNode();
        }

        // Index Validator for type multiple
        if($node->getType() === Properties::TYPE_MULTIPLE)
            $this->indexValidator = new ValueValidator($node->getIndex(), $this->statusManager, $this->factory);

        // Property validators
        foreach($node->getProperties() as $key => $subnode)
            $this->propertyValidator[$key] = $this->factory->createValidator($subnode, $this->statusManager);

        // Type if multiple
        if($node->getType() === Properties::TYPE_MULTIPLE){

            $children = array();
            foreach($values as $idx => $child) {

                $this->statusManager->enterPath($idx);

                $idx = $this->indexValidator->validate($idx, $this->data);

                if($idx !== null && \is_array($child)){
                    $children[$idx] = $this->applyDefinitions($child, $node);
                }

                $this->statusManager->exitPath();
            }
            return $children;
        }

        // Type is single
        if($node->getType() === Properties::TYPE_SINGLE) {
            return $this->applyDefinitions($values, $node);
        }

        // Type is invalid
        $this->addStatusMessage(
            StatusManager::ERROR,
            "Invalid schema type",
            $node->getType()
        );
        return null;
    }

    /**
     * @param array $values
     * @param \Topolis\Validator\Schema\Node\Properties $node
     * @return array
     * @throws \Exception
     */
    protected function applyDefinitions($values, $node){
        // Validate a value
        $valid = array();

        if(is_array($values)){
            $surpusKeys = array_keys(array_diff_key($values, $node->getProperties()));
            if($surpusKeys){
                $this->addStatusMessage(
                    StatusManager::SANITIZED,
                    "Sanitized - Additional keys present (".implode(",",$surpusKeys).")",
                    $values
                );
            }
        }

        /* @var INode $subnode */
        foreach($node->getProperties() as $key => $subnode){

            $this->statusManager->enterPath($key);
            $value = null;
            
            try {

                // Propery does not exist and no default specified
                if(!\is_array($values) || (!array_key_exists($key,$values) && $subnode->getDefault() === null)){
                    if ($subnode->getRequired())
                        throw new ValidatorException("Invalid - Required property is missing");
                }

                // Property exists
                else {
                    $value = is_array($values) && array_key_exists($key,$values) ? $values[$key] : $subnode->getDefault();

                    $value = $this->propertyValidator[$key]->validate($value, $this->data);

                    if($subnode->getRequired()){
                        // Depending on the type of a variable, different criteria apply to check if something is empty or not
                        switch(gettype($value)){
                            case "string":
                                $requiredError = $value === "";
                                break;
                            case "array":
                                $requiredError = $value === [];
                                break;
                            default:
                                $requiredError = $value === null;
                        }

                        if($requiredError)
                            throw new ValidatorException("Invalid - Required field is empty");
                    }

                    // FIXME: the "Remove empty properties" feature has been removed and needs to be reimplemented in a cleaner way if realy needed
                    $valid[$key] = $value;
                }

            } catch (ValidatorException $e) {
                // Error reporting
                $this->addStatusMessage(
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
