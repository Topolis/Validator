<?php

namespace Topolis\Validator\Schema;

use Topolis\Validator\StatusManager;

interface IValidator {

    public function __construct(INode $node, StatusManager $statusManager, NodeFactory $factory);
    public function validate($value, $data = null);

}
