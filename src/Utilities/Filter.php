<?php

namespace Topolis\Validator\Utilities;

/**
 * Class Filter
 * The Topolis/Filter libraray defines the type of values per default as "any". This allows single values and arrays/trees of values.
 * The Validator is more restrictive and changes this behaviour to a default of "single".
 *
 * @package Topolis\Validator\Utilities
 */
class Filter extends \Topolis\Filter\Filter {

    const TYPE_DEFAULT = self::TYPE_SINGLE;

}