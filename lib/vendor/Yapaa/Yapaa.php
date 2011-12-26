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

require_once __DIR__ . '/Weaver.php';

/**
 * Implementation of \Yapaa\Weaver
 * 
 * @author Tomáš Klapka
 */
class Yapaa implements \Yappa\Weaver {

    private function __construct() {
        
    }

    public static function weaveFunction($functionName) {
        if (!function_exists($functionName)) {
            throw new UndefinedFunctionException;
        }
        return new YapaaFunction($functionName);
    }

    public static function weaveClassMethod($className, $methodName) {
        if (!method_exists($className, $methodName)) {
            throw new UndefinedFunctionException;
        }
        return new YapaaFunction($methodName, $className);
    }

}

/**
 * Implementation of \Yappa\WeaveFunction
 * 
 * @author Tomáš Klapka
 */
class YapaaFunction implements \Yappa\WeaveFunction {
    const PREFIX = 'YAPAA_';
    const SUFIX = '_invokeOriginal';
    const TYPE_FUNCTION = 1;
    const TYPE_METHOD = 2;

    private $type = self::TYPE_FUNCTION;
    private $functionName = '';
    private $className = false;
    private $classIsStatic = false;
    private $classAccess = RUNKIT_ACC_PUBLIC;
    private $originalFunctionInvoke = '';
    private $adviceCode = '';
    private $codeBefore = '';
    private $codeAfter = '';
    private $exceptions = array();

    public function __construct($functionName, $className = false) {
        $this->functionName = $functionName;
        $this->className = $className;
        $this->originalFunctionInvoke = static::PREFIX . $this->functionName . static::SUFIX;
        if ($className) {
            $this->type = static::TYPE_METHOD;
            list ($this->classIsStatic, $this->classAccess) =
                    static::get_class_method_reflection_info($this->className, $this->functionName);
        }
    }

    public function addCutBefore($adviceCode) {
        $this->codeBefore .= ";$adviceCode;";
    }

    public function addCutAfter($adviceCode) {
        $this->codeAfter .= ";$adviceCode;";
    }

    public function addCutCatchException($exceptionName, $adviceCode) {
        if (!isset($this->exceptions[$exceptionName])) {
            $this->exceptions[$exceptionName] = '';
        }
        $this->exceptions[$exceptionName] .= $adviceCode;
    }

    public function weave() {
        $this->adviceCode = $this->buildFunctionAdviceString();
        if ($this->isWeaved()) {
            $this->weaveRedefine();
        } else {
            $this->weaveRenameAdd();
        }
    }

    private function buildFunctionAdviceString() {

        $new_func_ident = $this->buildFunctionIdentString();
        $new_func_call = "call_user_func_array($new_func_ident, \$args);";
        $exceptions = $this->buildExceptionsAdviceString();

        return "
            \$args = func_get_args();
            \$return = NULL;
            {$this->codeBefore};
            try {
                \$return .= $new_func_call;
            }
            " . ((strlen($exceptions) > 0) ? $exceptions : 'catch (Exception $e) { throw $e; }') . "
            {$this->codeAfter};
            return \$return;
        ";
    }

    private function buildFunctionIdentString() {
        if ($this->type === static::TYPE_FUNCTION) {
            $func_ident = "'$this->originalFunctionInvoke'";
        } else {
            if ($this->classIsStatic) {
                $func_ident = "'$this->className::$this->originalFunctionInvoke'";
            } else {
                $func_ident = "array('$this->className', '$this->originalFunctionInvoke')";
            }
        }
        return $func_ident;
    }

    private function buildExceptionsAdviceString() {
        $string = '';
        foreach ($this->exceptions as $exception => $advice) {
            $string .= "catch ($exception \$e) { $advice; }\n";
        }
        return $string;
    }

    protected static function get_class_method_reflection_info($class, $method) {
        $static = false;

        $reflection = new \ReflectionMethod($class, $method);
        $modifiers = $reflection->getModifiers();

        if ($modifiers & \ReflectionMethod::IS_STATIC) {
            $static = true;
        }

        $access = RUNKIT_ACC_PUBLIC;
        if ($modifiers & \ReflectionMethod::IS_PRIVATE) {
            $access = RUNKIT_ACC_PRIVATE;
        } elseif ($modifiers & \ReflectionMethod::IS_PROTECTED) {
            $access = RUNKIT_ACC_PROTECTED;
        }

        return array($static, $access);
    }

    public function isWeaved() {
        if (
                (($this->type === static::TYPE_FUNCTION) and
                /**/ (function_exists($this->originalFunctionInvoke))) or
                (($this->type === static::TYPE_METHOD) and
                /**/ (method_exists($this->className, $this->originalFunctionInvoke)))
        ) {
            return true;
        }
        return false;
    }

    private function weaveRedefine() {
        if ($this->type === static::TYPE_FUNCTION) {
            runkit_function_redefine($this->functionName, '', $this->adviceCode);
        } else {
            runkit_method_redefine($this->className, $this->functionName, '', $this->adviceCode, $this->classAccess);
        }
    }

    private function weaveRenameAdd() {
        if ($this->type === static::TYPE_FUNCTION) {
            runkit_function_rename($this->functionName, $this->originalFunctionInvoke);
            runkit_function_add($this->functionName, '', $this->adviceCode);
        } else {
            runkit_method_rename($this->className, $this->functionName, $this->originalFunctionInvoke);
            runkit_method_add($this->className, $this->functionName, '', $this->adviceCode, $this->classAccess);
        }
    }

}

/**
 * The exception that is thrown when the function to be weaved is not defined.
 */
class UndefinedFunctionException extends \RuntimeException {
    
}

/**
 * The exception that is thrown when the method to be weaved is not defined.
 */
class UndefinedMethodException extends \RuntimeException {
    
}
