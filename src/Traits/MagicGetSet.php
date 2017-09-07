<?php

namespace Topolis\Validator\Traits;

use Exception;

trait MagicGetSet {

    public function __get($name) {
        $method = "get".ucfirst($name);

        if(is_callable( [$this, $method] ))
            return $this->$method();

        throw new Exception("Invalid property '$name' requested");
    }

    public function __set($name, $value) {
        $method = "set".ucfirst($name);

        if(is_callable( [$this, $method] ))
            return $this->$method();

        throw new Exception("Invalid property '$name' requested");
    }
}