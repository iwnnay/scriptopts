<?php
namespace ScriptOpts;

class Options
{
    /*
     * @var array stores the options
     */
    private $scriptOptions = [];

    /*
     * #var array Short string to be parsed
     */
    private $shortOptions = [];

    /*
     * @var array Array of long options parsed
     */
    private $longOptions = [];

    /*
     * @var array Evaluated options
     */
    private $parseOptions;

    /*
     * @var array Evaluated options
     */
    private $specifiedUsageStatement;

    /*
     * @var array designations
     */
    private $designations;

    public function __construct()
    {
        $args = func_get_args();
        $this->specifiedUsageStatement(array_shift($args));
        $options = array_shift($args);
        $this->designations = $args;

        if (is_array($options[0])) {
            foreach($options as $option) {
                $this->add($option);
            }
        } else {
            $this->add($options);
        }


        $this->parseOptions();
    }

    private function specifiedUsageStatement($value = null)
    {
        if ($value && !is_string($value)) {
            throw \Exception('First parameter passed to Options must be a string');

        } elseif (is_null($value)) {
            return $this->specifiedUsageStatement;
        }

        $this->specifiedUsageStatement = $value;
    }

    public function get($option) {
        if (isset($this->parsedOptions[$option])) {
            return $this->parsedOptions[$option];
        } else {
            return null;
        }
    }

    public function displayManPage()
    {
        print $this->specifiedUsageStatement . "\n\n";
        foreach($this->scriptOptions as $option) {
            printf(
                "    -%1s | --%-15s : %s\n",
                str_replace(':', '', $option[1]),
                str_replace(':', '', $option[0]),
                $option[2]
            );
        }

        print "\n\n\n";
    }

    private function add($args)
    {
        if ($args[1]) {
            $this->shortOptions[] = $args[1];
        }
        $this->longOptions[] = $args[0];

        $this->scriptOptions[] = $args;
    }

    private function parseOptions()
    {
        $parsed = [];
        die('here');

        foreach($this->scriptOptions as $args) {
            $long = str_replace(':', '', array_shift($args));
            $short = str_replace(':', '', array_shift($args));
            $description = array_shift($args);

            if (isset($opts[$short])) {
                $parsed[$long] = $opts[$short] ?: true;
            }

            if (isset($opts[$long])) {
                $parsed[$long] = $opts[$long] ?: true;
            }

            if (!isset($parsed[$long])) {
                $parsed[$long] = false;
            }

            if ($parsed[$long] && $args) {
                $this->handleExtras($parsed[$long], $args);
            }
        }

        $this->parsedOptions = $parsed;
    }

    private function handleExtras($value, $extras)
    {
        if ($extras['callback']) {
            if (is_array($extras['callback'])) {
                list($object, $method) = $extras['callback'];
                $object->$method($value, $this);
            } else {
                $this->$extras['callback']($value);
            }
        }
    }
}
