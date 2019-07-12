<?php

namespace Topolis\Validator\Schema\Validators;

use Topolis\Validator\Schema\INode;
use Topolis\Validator\Schema\IValidator;
use Topolis\Validator\Schema\Node\Listing;
use Topolis\Validator\Schema\NodeFactory;
use Topolis\Validator\StatusManager;

abstract class BaseValidator implements IValidator {

    protected static $path = [];

    /* @var Listing $definition */
    protected $node;
    /* @var StatusManager $definition */
    protected $statusManager;
    /* @var NodeFactory $factory */
    protected $factory;

    public function __construct(INode $node, StatusManager $statusManager, NodeFactory $factory) {
        $this->node = $node;
        $this->statusManager = $statusManager;
        $this->factory = $factory;
    }

    /**
     * @param int $code
     * @param string $message
     * @param mixed $value
     * @param INode|null $node
     * @throws \Exception
     */
    protected function addStatusMessage($code, $message, $value, $node = null) {
        $message = $this->node->getErrorMessage($code, $message);
        $code = $this->node->getErrorCode($code);
        $node = $node ?? $this->node;

        $this->statusManager->addMessage($code, $message, $value, $node);
    }

}
