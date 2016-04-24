<?php
namespace ScriptOpts\Exceptions;

class NoShortValue extends \Exception {
    public function __construct($optName)
    {
        parent::__construct(
            "Could not determine the value of -$optName. " .
            "Either from improper usage or no value given\n"
        );
    }
}
