<?php

/**
 * This file is part of the Yapaa library - Yet another PHP AOP approach
 *
 * Copyright (c) 2011 Tomáš Klapka (tomas@klapka.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Yapaa;

require_once __DIR__ . '/IYapaa.php';

/**
 * Implementation of \Yapaa\IWeaver
 * 
 * @author Tomáš Klapka
 */
class RunkitWeaver implements IWeaver {

    public static function originalFunctionName($functionName) {
        return static::PREFIX . $functionName . static::SUFIX;
    }

    public static function isFunctionWeaved($functionName) {
        return function_exists(static::originalFunctionName($functionName));
    }

    public static function isMethodWeaved($className, $methodName) {
        return method_exists($className, static::originalFunctionName($methodName));
    }

    public static function weaveFunction($functionName, $adviceCode) {
        if (!function_exists($functionName)) {
            throw new YapaaException("Function $functionName does not exist.");
        }

        Yapaa::log("Weaving function $functionName", " by <<<'EOF'\n" .
                preg_replace('/\n/', "\n\t", $adviceCode) . "\nEOF\n");

        $code = "$adviceCode;";

        if (static::isFunctionWeaved($functionName)) {
            runkit_function_redefine($functionName, '', $code);
        } else {
            runkit_function_rename($functionName, static::originalFunctionName($functionName));
            runkit_function_add($functionName, '', $code);
        }
        return static::originalFunctionName($functionName);
    }

    public static function weaveMethod($className, $methodName, $adviceCode) {
        if (!method_exists($className, $methodName)) {
            throw new YapaaException("Method $className::$methodName does not exist.");
        }

        Yapaa::log("Weaving method $className::$methodName", " by <<<'EOF'\n" .
                preg_replace('/\n/', "\n\t", $adviceCode) . "\nEOF\n");

        $code = "$adviceCode;";

        $modifiers = static::getClassMethodModifiers($className, $methodName);

        if (static::isMethodWeaved($className, $methodName)) {
            $backtrace = debug_backtrace();
            $found = false;
            foreach ($backtrace as $call) {
                if (isset($call['class']) and
                        ($call['class'] === $className) and
                        ($call['function'] === $methodName)) {
                    $found = true;
                }
            }
            if (!$found) {
                runkit_method_redefine($className, $methodName, '', $code, $modifiers);
            }
        } else {
            runkit_method_rename($className, $methodName, static::originalFunctionName($methodName));
            runkit_method_add($className, $methodName, '', $code, $modifiers);
        }
        return static::originalFunctionName($methodName);
    }

    private static function getClassMethodModifiers($className, $methodName) {
        $reflection = new \ReflectionMethod($className, $methodName);
        $modifiers = $reflection->getModifiers();

        $return = RUNKIT_ACC_PUBLIC;
        if ($modifiers & \ReflectionMethod::IS_PRIVATE) {
            $return = RUNKIT_ACC_PRIVATE;
        } elseif ($modifiers & \ReflectionMethod::IS_PROTECTED) {
            $return = RUNKIT_ACC_PROTECTED;
        }

        if ($reflection->isStatic()) {
            $return |= RUNKIT_ACC_STATIC;
        }

        return $return;
    }

}
