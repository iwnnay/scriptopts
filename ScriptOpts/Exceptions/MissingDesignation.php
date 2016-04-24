<?php
namespace ScriptOpts\Exceptions;

class MissingDesignation extends \Exception
{
    public function __construct($value)
    {
        parent::__construct(
            "This script does not have an intended use for $value.\n"
        );
    }
}
