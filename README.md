# ScriptOpts

PHP library to help pass options and make a quick help screen for command line scripts

# Installation

ScriptOpts is designed to be a portable library with one main class and a series of exceptions. It can of course be cloned directly from GitHub, but I'd highly recommend downloading it using [Composer](https://getcomposer.org/). With composer the installation is fairly regular.

If you do not have a `composer.json` already in project root then you can run

    composer init

You can either add it during the interactive part of the `composer init` or enter it after in the `composer.json` like so:

    "require": {
        "iwnnay/scriptopts": "~1.0.0"
    }

and then run

    composer install

#### OR
If you're not keen on manually installing it you can always just do this instead:

    composer require iwnnay/scriptopts:1.*

## Usage

As always, when using Composer to install your applications you'll have access to the handy `vendor/autoload.php`. You should either be requiring that in your app or right away in the script in which you intend to use ScriptOpts.

    <?php
    require __DIR__ . '/vendor/autoload.php';

### Initial options

The power of using ScriptOpts should be apparent in this section. Instead of having to manually write a man page or defining the options and then attributing them manually you can simply call the public status method `parseNow()`

##### Definition

    ScriptOpts\Options::parseNow(array $options, [string ..$designation]);

_$options_: (array) This is an array of arrays that will be arguments used to quickly build out the options needed for your scripts. You can indicate that an option should take a value by adding a `:` after the long and short name.

_$designation_: You can enter as many designations as you want. When a user of the script passes a non-option argument to the script it will be assigned in three places.

##### Example

    $options = new Options::parseNow(
        [
            ['longName', 'l', 'Description of this option'],
            ['takesValue:', 't', 'Description of an option that takes a value'],
            ['hasCallBack', 'c', 'Uses a scoped callback($value, Options)',
              'callback' => [$this, 'functionOrFunctionName']
            ]
            //default option ['help', 'h', 'Displays help screen']
        ],
        '*requiredParam',
        'optionalParam'
    );

    function functionOrFunctionName($value, $options)
    {
        return $valueThatHasBeenChangedOrChecked;
    }

    $options->get('longName'); // (bool) true if --longName or -l were used options
    $options->get('longName'); //  null if --longName or -l were NOT used options

    $options->get('takesValue'); // (string) $value if --takesValue=value || --takesValue value || -t value
    $options->get('takesValue'); // null if --takesValue || -t were NOT used

    $options->get('hasCallBack'); // (mixed) value returned from function
    $options->get('hasCallBack'); // null if --hasCallBack or -c were NOT used

    $options->get('notUsed'); // null

##### Designations

If a designation is entered it can be accessed one of three ways.

    // This is the most secure way and inline with other usages
    $options->get('requiredParam');

    // It will be added back into the script $argv
    global $argv;
    $argv['requiredParam'];

    // And if there are no collisions with existing attributes you can also use
    $options->requiredParam;

### Custom Error Handler

If you don't like the way that errors are being handled by default you can add your own custom error handler like so:

    $options = new Options($usageStatement, $optionsArray);
    $options->setCustomErrorHandler(function(){
        die('with custom error every time');
    });
    $options->parse();

### Other Public Methods

_getOptions_: Returns an array of all the discovered parsed options and designations

    $options = Options::ParseNow(...);
    $options->getOptions(); // [ 'passedOption' => 'parsedValue' ]

_displayManPage_: Prints out the usage statement and available options. **Does not cause script to die**

    $options = Options::ParseNow(...);
    $options->displayManPage();

_setUsageStatement_: Takes passed var and uses that when displaying man page instead of generated usage statement

    $options = new Options(...);
    $options->setUsageStatement('usage: Something Unusual -42');

_getUsageStatement_: Retrieves either the set or generated usage statement depending on whichever is relevant

    $options = new Options(...); // or parseNow()
    $options->getUsageStatement();

### Author

My name is Joe Imhoff, you can reach me at iwnnay@gmail.com if you have any questions, concerns or suggestions about ScriptOpts or any other packages I've built.

I was in Ruby land for quite some time as my main language and now that I'm back in PHP I'm happy to be part of a thriving community of developers that have made it easy to share projects like this one.

Enjoy!
