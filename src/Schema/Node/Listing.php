<?php

namespace Topolis\Validator\Schema\Node;

use Topolis\Validator\Schema\INode;

class Listing implements INode {

    public static function detect($schema) {
        return is_array($schema) and isset($schema["listing"]);
    }

}