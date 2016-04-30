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
    private $usageStatement;

    /*
     * @var array designations
     */
    private $designations;

    /*
     * @var array originalDesignations
     */
    private $originalDesignations;

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

    /**
     * Instantiates class
     *
     * @param array $options
     * @param collection ..$designations
     */
    public function __construct()
    {
        $args = func_get_args();
        $options = array_shift($args);
        $this->designations = $this->originalDesignations = $args;

        if (isset($options[0]) && is_array($options[0])) {
            foreach($options as $option) {
                $this->add($option);
            }
        } elseif ($options) {
            $this->add($options);
        }

        $this->addHelpOption();
    }

    /**
     * Passes parameters to a new instance of itself and parses the options
     *
     * @param array $options
     * @param collection ..$designations
     */
    public static function parseNow()
    {
        $options = new \ReflectionClass('ScriptOpts\Options');
        $options = $options->newInstanceArgs(func_get_args());
        return $options->parse();
    }

    /**
     * Runs over the options passed to the script and compares it to the desired
     * outcome of the instatiated options array
     *
     * @return Options
     */
    public function parse()
    {
        try {
            $this->parseOptions();
        } catch(\Exception $error) {
            $this->handleError($error);
        }

        return $this;
    }

    /**
     * Overrides default error handler with the function that is passed
     *
     * @param callable  $errorHandlerFunction
     * @return void
     */
    public function setErrorHandler($errorHandlerFunction)
    {
        if (is_callable($errorHandlerFunction)) {
            $this->customErrorHandler = $errorHandlerFunction;
        }
    }

    /**
     * Pulls a value from the parsed options or returns null
     *
     * @param string indicating desired value by key
     * @return mixed the value from the array
     */
    public function get($option) {
        if (isset($this->parsedOptions[$option])) {
            return $this->parsedOptions[$option];
        } else {
            return null;
        }
    }

    /**
     * Returns the entire array of parse options
     *
     * @return array $this->parsedOptions
     */
    public function getOptions()
    {
        return $this->parseOptions;
    }

    /**
     * Prints the generated man page onto the screen.
     *
     * @return void
     */
    public function displayManPage()
    {
        print $this->getUsageStatement() . "\n\n";
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

    /**
     * Overrides generated usage statement with passed value
     *
     * @return void
     */
    public function setUsageStatement($statement)
    {
        $this->usageStatement = $statement;
    }

    /**
     * Retrieves either the already set or generated usageStatement
     *
     * @return string
     */
    public function getUsageStatement()
    {
        if ($this->usageStatement) {
            return $this->usageStatement;
        }

        return $this->usageStatement = 'usage: ' .
          "{$this->parsedOptions['scriptName']} [options] {$this->displayDesignations()}";
    }

    /**
     * gets string of designations formatted for usage statement
     *
     * @return string
     */
    private function displayDesignations()
    {
        $designations = [];
        foreach ($this->originalDesignations as $designation) {
            if (strpos($designation, '*') === 0) {
                $designation = ltrim($designation, '*');
                $designations[] = "<$designation>";
            } else {
                $designations[] = "[$designation]";
            }
        }

        return implode(' ', $designations);
    }

    /**
     * Centralized Handling of all parse errors by displaying the error then the
     * man page and then dies
     *
     * @param Exception $error
     * @return void
     */
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

    /**
     * The first step in the process of actually parsing the options
     *
     * @return void
     */
    private function parseOptions()
    {
        global $argv;
        $args = $argv;
        $this->parsedOptions['scriptName'] = basename(array_shift($args));

        while($args) {
            $inQuestion = array_shift($args);

            if (strpos($inQuestion, '--') === 0) {
                $this->handleLong(ltrim($inQuestion, '--'), $args);

            } elseif (strpos($inQuestion, '-') === 0) {
                foreach(str_split(ltrim($inQuestion, '-')) as $key) {
                    if ($key == '=') {
                        break;
                    }

                    $this->handleShort($inQuestion, $key, $args);
                }

            } else {
                $this->handleDesignation($inQuestion);
            }
        }
        $this->checkRequiredDesignations();
    }

    /**
     * Runs when parsing is complete to make sure all required designations
     * were assigned
     *
     * @return void
     */
    private function checkRequiredDesignations()
    {
        foreach($this->designations as $designation) {
            if (strpos($designation, '*') === 0) {
                throw new Exceptions\MissingRequiredDesignation(ltrim($designation, '*'));
            }
        }
    }

    /**
     * Puts an option into the parser so that when the parser is ran the values
     * and fields can be determined
     *
     * @param array $args
     * @return void
     */
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

    /**
     * Adds the default help option if a help option has not already been assigned
     *
     * @return void
     */
    private function addHelpOption()
    {
        if (!isset($this->longOptions['help'])) {
            $this->add(['help', 'h', 'Display this screeen',
                'callback' => function ($value, $options) {
                    $options->displayManPage();
                    die();
                }
            ]);
        }
    }

    /**
     * If a long value is parsed from the options given to the script what to
     * with it is determined here. It is then set on parsedOptions
     *
     * @param string long option name passed to script
     * @param array the rest of the arguments yet to be parsed
     * @return void
     */
    private function handleLong($opt, &$otherArguments) {
        $value = null;
        $inLong = array_search($opt, $this->longOptions);
        if ($inLong !== false) {
            $this->parsedOptions[$opt] = true;
        } else {

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
        }

        if (isset($this->parsedOptions[$opt])) {
            $this->handleSpecifications($opt);
        } else {
            throw new Exceptions\InvalidOption("--$opt");
        }
    }

    /**
     * If a short value is parsed from the options given to the script what to
     * with it is determined here. It is then set on parsedOptions by the
     * corresponding longName key
     *
     * @param string raw agrument that is being assessed
     * @param array short name option passed to script
     * @param other arguments yet to be parsed
     * @return void
     */
    private function handleShort($arg, $opt, &$otherArguments)
    {
        $longName = null;

        $inShort = array_search($opt, $this->shortOptions);
        if ($inShort !== false) {
            $longName = $this->longOptions[$inShort];
            $this->parsedOptions[$longName] = true;
        } else {

            if(preg_match("/^-$opt=/", $arg) != false) {
                list($arg, $value) = explode('=', $arg)[1];
            }

            $inShort = array_search($opt.':', $this->shortOptions);
            if ($inShort !== false) {
                if (!$value) {
                    $value = array_shift($otherArguments);
                }

                if (strpos($arg, $opt) != strlen($arg) - 1 || is_null($value)) {
                    throw new Exceptions\NoShortValue($opt);
                }

                $longName = rtrim($this->longOptions[$inShort], ':');
                $this->parsedOptions[$longName] = $value;
            }
        }

        if (isset($this->parsedOptions[$longName])) {
            $this->handleSpecifications($longName);
        } else {
            throw new Exceptions\InvalidOption("-$opt");
        }
    }

    /**
     * When it is determined that the script argument is not an option it
     * is passed to this method in order to determine its value and home
     *
     * @param string $value
     * @return void
     */
    private function handleDesignation($value)
    {
        global $argv;
        $var = array_shift($this->designations);

        if (is_null($var)) {
            throw new Exceptions\MissingDesignation($value);
        }

        $var = trim($var, '*');

        $this->parsedOptions[$var] = $value;
        $argv[$var] = $value;

        if (!isset($this->$var)) {
            $this->$var = $value;
        }
    }

    /**
     * When a value is parsed this handler makes sure that nothing else needs to
     * happen by evaluating the other specifications in the given options array
     *
     * @param string $longName
     * @return void
     */
    private function handleSpecifications($longName)
    {
        $extras = $this->specifications[$longName];
        $value = $this->parsedOptions[$longName];

        if (isset($extras['callback'])) {
            if (is_array($extras['callback'])) {
                list($object, $method) = $extras['callback'];

                $object->$method($value, $this);

            } else {
                $extras['callback']($value, $this);
            }
        }
    }
}

