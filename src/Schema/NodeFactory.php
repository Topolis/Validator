<?php

namespace Topolis\Validator\Schema;

use Exception;
use Topolis\Validator\StatusManager;

class NodeFactory {

    protected $registry = [];

    public function registerClass($class) {

        // Fastest available interface test for a name of a class according to https://stackoverflow.com/questions/274360/checking-if-an-instances-class-implements-an-interface
        $valid = isset( class_implements($class, true)['Topolis\Validator\Schema\INode'] );

        if(!$valid)
            throw new Exception("Invalid node type registered: ".$class);

        $this->registry[] = $class;
    }

    public function createNode(array $schema) {

        foreach ($this->registry as $class) {

            /* @var $class INode */
            if( !$class::detect($schema) )
                continue;

            $node = new $class($schema, $this);
            return $node;
        }

        throw new Exception("Unknown schema node found with keys: ".implode(", ",array_keys($schema)));
    }

    public function createValidator(INode $node, StatusManager $statusManager) {

        $class = $node::validator();
        return new $class($node, $statusManager, $this);
    }

}