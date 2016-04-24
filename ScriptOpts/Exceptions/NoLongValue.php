<?php
namespace ScriptOpts\Exceptions;

class NoLongValue extends \Exception {
    public function __construct($optName)
    {
        parent::__construct(
            "Could not determine the value of --$optName\n"
        );
    }
}
