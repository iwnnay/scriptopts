<?php
namespace ScriptOpts\Exceptions;

class MissingRequiredDesignation extends \Exception
{
    public function __construct($designation)
    {
        parent::__construct("Missing a required parameter: $designation\n");
    }
}
