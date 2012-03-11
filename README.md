# Yapaa - Yet Another PHP AOP Approach #

Yapaa is a PHP library which uses [runkit](http://php.net/manual/en/book.runkit.php) extension. I don't like approach of other libraries. Most of them is outdated or uses proxy objects but I like metaprogramming and runkit allows to weave functions as well as methods. So I started my own implementation.

Here is just a small description of what it does and how to use it. After reading this page, check [Yapaa interface](https://gitorious.org/yapaa/yapaa/blobs/master/lib/vendor/Yapaa/IYapaa.php), [Runkit Weaver implementation](https://gitorious.org/yapaa/yapaa/blobs/master/lib/vendor/Yapaa/RunkitWeaver.php) and [tests](https://gitorious.org/yapaa/yapaa/trees/master/tests/lib/vendor/Yapaa) ([YapaaTest](https://gitorious.org/yapaa/yapaa/blobs/master/tests/lib/vendor/Yapaa/YapaaTest.php))

## Quick Start

    $pointcut = \Yapaa\Yapaa::Pointcut(array(
        // all functions starting str_
        'function(str_*)', 

        // function not_defined_function_yet()
        'function(not_defined_function_yet)',

        // for someMethod() from any class starting My and ending Class
        'method(My*Class,someMethod)',
    ));

    $pointcut
        // add before advice
        ->addAdviceBefore('echo "before aspect\n";')

        // add after advice
        ->addAdviceAfter('echo "after aspect\n";')

        // add around advice and invoke original function somewhere inside
        ->addAdviceAround('
            echo "around aspect before\n";
            Yapaa::proceed();
            echo "around aspect after\n";
        ')

        // and weave
       ->weave();

    // include function which was not defined when weave was called
    // but it would have matched to 'function(not_defined_function_yet)'
    include "not_defined_function_yet.inc.php";

    // weave all matching joinpoints, i.e.
    // it founds and weaves not_defined_function_yet()
    \Yapaa\Yapaa::weaveAllPointcuts();

## Explanation

`\Yapaa\Yapaa::Pointcut($pointcut)` finds all matching functions and methods and defines joinpoints to these.

Currently supported pointcuts are:

* `function(function_name_wildcard)`
* `method(class_name_wildcard,method_name_wildcard)`

Where wildcard means that you can use * as none or any character

Then you can add advices by add\*Advice* method.
Currently supported add methods:

* `addAdviceBefore($adviceCode)` - adds $adviceCode to be run before execution of the joinpoint function
* `addAdviceAfter($adviceCode)` - adds $adviceCode after the execution of the joinpoint function
* `addAdviceAround($adviceCode)` - replaces the joinpoint function by $adviceCode. You can use Yapaa::proceed() to run the original code (even multiple times)
* `addExceptionAdvice($exceptionName, $adviceCode)` - surrounds the joinpoint function by `try {}` block and adds `catch ($exceptionName) { $adviceCode }`

After you add all your advices you have to run `weave()` method to build the replacement function and actually weave it.

`\Yapaa\Yapaa::weaveAllPointcuts()` runs the matching mechanism again and weaves all matching joinpoints again.

This is usefull when using autoloading. You can weave this to be run after autoloader mechanism is executed.
Assuming that your loader method is declared as `MyLoader::Load($filename)`

    $autoloaderPointcut = new \Yapaa\Yapaa::Pointcut('function(MyLoader,Load)');
    $autoloaderPointcut->addAdviceAfter('\Yapaa\Yapaa::weaveAllPointcuts();')

## Arguments, return value and other special variables

Function arguments are saved in `$argv` and number of parameters is in `$argc`. These can be used or modified in all advices (actually usefull only before and before proceeding in around advice).
Function return value is saved in `$return` and it can be used or modified in after and around advices.
Class is saved into `$className` and method or function name is saved into `$functionName`.

## PHP Internal functions

To allow weaving of PHP internal functions you have to set `runkit.internal_override=1` in your `php.ini` file and call static method `JoinPoint::includeInternals();` before creating pointcut.
PHP language constructs (die, echo, empty, exit, eval, include, include_once, isset, list, require, require_once, return, print, unset) cannot be weaved.

## Debugging

It is difficult to debug aspects. If you want to see what Yapaa does or does not you can turn on logging to file:

    // turns on logging to file
    \Yapaa\Yapaa::logToFile(__DIR__.'/../log/yapaa.log');

    // if you want to see redefined code of your functions
    // turn on verbose logging
    \Yapaa\Yapaa::logVerbose();

## Warning

Library is not even alpha version. It is just something I'm going to try to use myself and I'll see how it goes. It means it is highly to be changed, redesigned, refactored.

