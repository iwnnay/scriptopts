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

    /*
     * @var array specifications
     */
    private $specifications;

    /*
     * @var array parsedOptions
     */
    private $parsedOptions = [];

    /*
     * @var object customErrorHandler
     */
    private $customErrorHandler;

    public function __construct()
    {
        $args = func_get_args();
        $this->specifiedUsageStatement(array_shift($args));
        $options = array_shift($args);
        $this->designations = $args;

        if (isset($options[0]) && is_array($options[0])) {
            foreach($options as $option) {
                $this->add($option);
            }
        } elseif ($options) {
            $this->add($options);
        }

        $this->addHelpOption();
    }

    public static function parseNow()
    {
        $options = new \ReflectionClass('ScriptOpts\Options');
        $options = $options->newInstanceArgs(func_get_args());
        return $options->parse();
    }

    public function parse()
    {
        try {
            $this->parseOptions();
        } catch(\Exception $error) {
            $this->handleError($error);
        }

        return $this;
    }

    public function setErrorHandler($errorHandlerFunction)
    {
        if (is_callable($errorHandlerFunction)) {
            $this->customErrorHandler = $errorHandlerFunction;
        }
    }

    public function get($option) {
        if (isset($this->parsedOptions[$option])) {
            return $this->parsedOptions[$option];
        } else {
            return null;
        }
    }

    public function getOptions()
    {
        return $this->parseOptions;
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

    private function handleError($error)
    {
        if (is_callable($this->customErrorHandler)) {
            call_user_func($this->customErrorHandler, $error);
        }

        print "---\n";
        print $error->getMessage();
        print "---\n";

        $this->displayManPage();
        die();
    }

    private function parseOptions()
    {
        global $argv;
        $args = $argv;
        $this->parsedOptions['scriptName'] = array_shift($args);

        while($args) {
            $inQuestion = array_shift($args);

            if (strpos($inQuestion, '--') === 0) {
                $this->handleLong(ltrim($inQuestion, '--'), $args);

            } elseif (strpos($inQuestion, '-') === 0) {
                foreach(str_split(ltrim($inQuestion, '-')) as $key) {
                    $this->handleShort($inQuestion, $key, $args);
                }

            } else {
                $this->handleDesignation($inQuestion);
            }
        }
        $this->checkRequiredDesignations();
    }

    private function checkRequiredDesignations()
    {
        foreach($this->designations as $designation) {
            if (strpos($designation, '*') == 0) {
                throw new Exceptions\MissingRequiredDesignation(ltrim($designation, '*'));
            }
        }
    }

    private function add($args)
    {
        $this->scriptOptions[] = $args;
        $long = array_shift($args);

        $this->longOptions[] = $long;
        $this->shortOptions[] = array_shift($args);
        $long = rtrim($long, ':');
        $this->specifications[$long]['description'] = array_shift($args);

        $this->specifications[$long] =
            array_merge($this->specifications[$long], $args);
    }

    private function addHelpOption()
    {
        if (!isset($this->longOptions['help'])) {
            $this->add(['help', 'h', 'Display this screeen',
                'callback' => [$this, 'displayManPage']]);
        }
    }

    private function specifiedUsageStatement($value = null)
    {
        if ($value && !is_string($value)) {
            throw new \Exception('First parameter passed to Options must be a string');

        } elseif (is_null($value)) {
            return $this->specifiedUsageStatement;
        }

        $this->specifiedUsageStatement = $value;
    }

    private function handleLong($opt, &$otherArguments) {
        $value = null;
        $inLong = array_search($opt, $this->longOptions);
        if ($inLong !== false) {
            $this->parsedOptions[$opt] = true;
        }

        if(strpos($opt, '=') != false) {
            list($opt, $value) = explode('=', $opt);
        }

        $inLongWithValue = array_search($opt.':', $this->longOptions);
        if ($inLongWithValue !== false) {
            if (!$value) {
                $value = array_shift($otherArguments);
            }

            if (is_null($value)) {
                throw new Exceptions\NoLongValue($opt);
            }

            return $this->parsedOptions[$opt] = $value;
        }

        if (isset($this->parsedOptions[$opt])) {
            $this->handleSpecifications($opt);
        } else {
            throw new Exceptions\InvalidOption("--$opt");
        }
    }

    private function handleShort($arg, $opt, &$otherArguments)
    {
        $longName = null;

        $inShort = array_search($opt, $this->shortOptions);
        if ($inShort !== false) {
            $longName = $this->longOptions[$inShort];
            $this->parsedOptions[$longName] = true;
        }

        $inShort = array_search($opt.':', $this->shortOptions);
        if ($inShort !== false) {
            $value = array_shift($otherArguments);

            if (strpos($arg, $opt) != strlen($arg) - 1 || is_null($value)) {
                throw new Exceptions\NoShortValue($opt);
            }

            $longName = str_replace(':', '', $this->longOptions[$inShort]);
            $this->parsedOptions[$longName] = $value;
        }

        if (isset($this->parsedOptions[$longName])) {
            $this->handleSpecifications($longName);
        } else {
            throw new Exceptions\InvalidOption("-$opt");
        }
    }

    private function handleDesignation($value)
    {
        global $argv;
        $var = array_shift($this->designations);

        if (is_null($var)) {
            throw new Exceptions\MissingDesignation($value);
        }

        $this->parsedOptions[$var] = $value;
        $this->$var = $value;
        $argv[$var] = $value;
    }

    private function handleSpecifications($longName)
    {
        $extras = $this->specifications[$longName];
        if (isset($extras['callback'])) {
            $value = $this->parsedOptions[$longName];
            if (is_array($extras['callback'])) {
                list($object, $method) = $extras['callback'];
                $object->$method($value, $this);
            } else {
                $this->$extras['callback']($value);
            }
        }
    }
}
