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

    protected $adviceCode = '';
    protected static $weaver = '\Yapaa\RunkitWeaver';
    protected $type = NULL;
    protected $originalFunctionName = '';

    public function setAdviceCode($adviceCode) {
        $this->adviceCode = &$adviceCode;
        return $this;
    }

    static public function setWeaver($weaver) {
        self::$weaver = $weaver;
    }

    public function getType() {
        return $this->type;
    }

    public function getOriginalFunctionName() {
        return $this->originalFunctionName;
    }

    static protected function filterMask($mask, $array) {

        $regexp = '/^(.*\\\)?'.preg_replace(
                '/%/', '[\w\d]*', preg_quote(
                        preg_replace('/\*/', '%', $mask))).'$/i';
        $filtered = array();
        foreach ($array as $element) {
            if (preg_match($regexp, $element)) {
                $filtered[] = $element;
            }
        }
        return $filtered;
    }

    public function weave()
    {
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
        $matching_functions = static::filterMask($mask, $defined_functions['user']);
        $joinPoints = array();
        foreach ($matching_functions as $functionName) {
            $joinPoint = new JoinPointFunction($functionName);
            $joinPoints[] = $joinPoint;
        }
        return $joinPoints;
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
        $joinPoints = array();
        foreach ($matching_classes as $className) {
            $matching_methods = static::filterMask($methodMask, get_class_methods($className));
            foreach ($matching_methods as $methodName) {
                $joinPoint = new JoinPointMethod($className, $methodName);
                $joinPoints[] = $joinPoint;
            }
        }
        return $joinPoints;
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

