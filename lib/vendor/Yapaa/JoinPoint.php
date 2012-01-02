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
 * Implementation of \Yapaa\IJoinPoint
 * 
 * @author Tomáš Klapka
 */
abstract class JoinPoint implements IJoinPoint {

    protected static $weaver = '\Yapaa\RunkitWeaver';
    protected static $includeInternals = false;
    protected static $joinPoints = array();
    protected $adviceCode = '';
    protected $type = NULL;
    protected $originalFunctionName = '';
    protected $pointcuts = array();

    public function setAdviceCode($adviceCode) {
        $this->adviceCode = &$adviceCode;
        return $this;
    }

    static public function setWeaver($weaver) {
        self::$weaver = $weaver;
    }

    public function addPointcut(IPointcut $pointcut) {
        if (!in_array($pointcut, $this->pointcuts)) {
            $this->pointcuts[] = $pointcut;
        }
        return $this;
    }

    public function buildAdvice() {
        $new_func_ident = $this->buildFunctionIdentString();
        $new_func_call = "\$return .= call_user_func_array($new_func_ident, ((\$argc > 0) ? \$argv : array()));";
        list($exceptionsBegin, $exceptionsEnd) = $this->buildExceptionsAdviceString();
        $codeBefore = $this->buildBeforeAdviceString();
        $codeAround = $this->buildAroundAdviceString($new_func_call);
        $codeAfter = $this->buildAfterAdviceString();

        if ($this->getType() === IJoinPoint::TYPE_FUNCTION) {
            $className = '';
            $functionName = $this->getFunctionName();
        } else {
            $className = $this->getClassName();
            $functionName = $this->getMethodName();
        }

        $advice = '
$argc = func_num_args();
$argv = func_get_args();
$className = "' . $className . '";
$functionName = "' . $functionName . '";
$return = NULL;' . "\n" .
                $codeBefore . ";\n" .
                $exceptionsBegin . "\n" .
                $codeAround . ";\n" .
                $exceptionsEnd . "\n" .
                $codeAfter . ";\n" .
                'return $return' . ";\n";

        $this->adviceCode = $advice;
        return $advice;
    }

    private function buildFunctionIdentString() {
        if ($this->getType() == IJoinPoint::TYPE_FUNCTION) {
            $func_ident = "'" . $this->getOriginalFunctionName() . "'";
        } else {
            $className = $this->getClassName();
            $reflection = new \ReflectionMethod($className, $this->getMethodName());
            if ($reflection->isStatic()) {
                $func_ident = "'" . $className . "::" . $this->getOriginalFunctionName() . "'";
            } else {
                $func_ident = "array('" . $className . "', '" . $this->getOriginalFunctionName() . "')";
            }
        }
        return $func_ident;
    }

    private function buildExceptionsAdviceString() {
        $string = '';
        $exceptions = array();
        foreach ($this->pointcuts as $pointcut) {
            $advices = $pointcut->getAdvices();
            foreach ($advices['exception'] as $exception => $advices) {
                if (!isset($exceptions[$exception])) {
                    $exceptions[$exception] = array();
                }
                $exceptions[$exception] = join("; ", $advices);
            }
        }
        foreach ($exceptions as $exception => $advice) {
            $string .= "catch ($exception \$e) { $advice; }\n";
        }
        if (strlen($string) > 0) {
            return array("try {\n", "\n} $string");
        }
        return array('', '');
    }

    private function buildAdviceString($where) {
        $string = '';
        foreach ($this->pointcuts as $pointcut) {
            $advices = $pointcut->getAdvices();
            $string .= implode(";\n", $advices[$where]);
        }
        return $string;
    }

    private function buildBeforeAdviceString() {
        return $this->buildAdviceString('before');
    }

    private function buildAfterAdviceString() {
        return $this->buildAdviceString('after');
    }

    private function buildAroundAdviceString($new_func_call) {

        $string = $new_func_call;
        foreach ($this->pointcuts as $pointcut) {
            $advices = $pointcut->getAdvices();
            foreach ($advices['around'] as $aroundAdvice) {
                $string = str_replace(IPointcut::KEYWORD_PROCEED, "$string;", $aroundAdvice);
            }
        }
        return $string;
    }

    static public function includeInternals() {
        $internal_override = ini_get('runkit.internal_override');
        if ($internal_override) {
            self::$includeInternals = true;
        } else {
            throw new YapaaException("Cannot include internal functions unless runkit.internal_override is turned on in php.ini");
        }
    }

    static public function excludeInternals() {
        self::$includeInternals = false;
    }

    public function getType() {
        return $this->type;
    }

    public function getOriginalFunctionName() {
        return $this->originalFunctionName;
    }

    static protected function filterMask($mask, $array) {

        $regexp = '/^(.*\\\)?' . preg_replace(
                        '/%/', '\w*', preg_quote(
                                preg_replace('/\*/', '%', $mask))) . '$/i';
        $filtered = array();
        foreach ($array as $element) {
            if (preg_match($regexp, $element)) {
                $filtered[] = $element;
            }
        }
        return $filtered;
    }

    public function weave() {
        /* declared because of interface */
    }

}

class JoinPointFunction extends JoinPoint {

    private $functionName = '';

    public function __construct($functionName) {
        $this->functionName = $functionName;
        $this->type = self::TYPE_FUNCTION;
        $weaver = self::$weaver;
        $this->originalFunctionName = $weaver::originalFunctionName($functionName);
    }

    public function getFunctionName() {
        return $this->functionName;
    }

    public static function findMatching($mask) {
        $defined_functions = get_defined_functions();
        $user_functions = $defined_functions['user'];
        $internal_functions = array();
        if (self::$includeInternals) {
            $internal_functions = $defined_functions['internal'];
        }
        $functions_to_match = array_merge($user_functions, $internal_functions);
        $matching_functions = static::filterMask($mask, $functions_to_match);
        $joinPoints = array();
        foreach ($matching_functions as $functionName) {
            Yapaa::log("JoinPoint found for function($mask): $functionName");
            $joinPoint = self::findExistingJoinPoint($functionName);
            if (!$joinPoint) {
                $joinPoint = new JoinPointFunction($functionName);
                self::$joinPoints[] = $joinPoint;
            }
            $joinPoints[] = $joinPoint;
        }
        return $joinPoints;
    }

    public static function findExistingJoinPoint($functionName) {
        foreach (self::$joinPoints as $joinPoint) {
            if (($joinPoint->getType() == self::TYPE_FUNCTION) and
                    ($joinPoint->getFunctionName() == $functionName)) {
                Yapaa::log("JoinPoint already exists for $functionName");
                return $joinPoint;
            }
        }
        return false;
    }

    public function weave() {
        if (strlen($this->adviceCode) === 0) {
            return $this;
        }
        $weaver = self::$weaver;
        $this->originalFunctionName = $weaver::weaveFunction($this->functionName, $this->adviceCode);
        return $this;
    }

}

class JoinPointMethod extends JoinPoint {

    private $className = '';
    private $methodName = '';

    public function __construct($className, $methodName) {
        $this->className = $className;
        $this->methodName = $methodName;
        $this->type = self::TYPE_METHOD;
        $weaver = self::$weaver;
        $this->originalFunctionName = $weaver::originalFunctionName($methodName);
    }

    public function getClassName() {
        return $this->className;
    }

    public function getMethodName() {
        return $this->methodName;
    }

    public static function findMatching($classMask, $methodMask) {
        $matching_classes = static::filterMask($classMask, get_declared_classes());
        Yapaa::log("classes matching $classMask: '" . join("','", $matching_classes) . "'");
        $joinPoints = array();
        foreach ($matching_classes as $className) {
            $matching_methods = static::filterMask($methodMask, get_class_methods($className));
            foreach ($matching_methods as $methodName) {
                Yapaa::log("JoinPoint found for method($classMask,$methodMask): $className::$methodName");
                $joinPoint = self::findExistingJoinPoint($className, $methodName);
                if (!$joinPoint) {
                    $joinPoint = new JoinPointMethod($className, $methodName);
                    self::$joinPoints[] = $joinPoint;
                }
                $joinPoints[] = $joinPoint;
            }
        }
        return $joinPoints;
    }

    public static function findExistingJoinPoint($className, $methodName) {
        foreach (self::$joinPoints as $joinPoint) {
            if (($joinPoint->getType() == self::TYPE_METHOD) and
                    ($joinPoint->getClassName() == $className) and
                    ($joinPoint->getMethodName() == $methodName)) {
                Yapaa::log("JoinPoint already exists for $className::$methodName");
                return $joinPoint;
            }
        }
        return false;
    }

    public function weave() {
        if (strlen($this->adviceCode) === 0) {
            return $this;
        }
        $weaver = self::$weaver;
        $this->originalFunctionName = $weaver::weaveMethod($this->className, $this->methodName, $this->adviceCode);
        return $this;
    }

}

