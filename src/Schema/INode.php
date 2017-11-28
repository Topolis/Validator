<?php

namespace Topolis\Validator\Schema;

interface INode {

    public static function detect(array $schema);
    public static function validator();

    public function __construct(array $schema, NodeFactory $factory);
    public function import(array $schema);
    public function export();
    public function merge(INode $node);

}
