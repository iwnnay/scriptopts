<?php
namespace ScriptOpts\Exceptions;

class InvalidOption extends \Exception {
    public function __construct($optionName) {
        parent::__construct(
            "$optionName  does not appear to be a valid option\n"
        );
    }
}
