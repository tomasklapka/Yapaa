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

use Yapaa;

/**
 * Static factory for Pointcuts
 * 
 * @author Tomáš Klapka
 */
interface IYapaa {

    /**
     * Creates and returns Pointcut
     * @param  string
     * @return \Yapaa\IPointcut
     */
    public static function Pointcut($pointcutMask);

    /**
     * Refresh all aspects. (To be weaved to after autoload function)
     */
    public static function weaveAllPointcuts();
}

/**
 * Pointcut
 * 
 * @author Tomáš Klapka
 */
interface IPointcut {
    const KEYWORD_PROCEED = 'Yapaa::proceed();';
    const KEYWORD_FUNCTION = 'function';
    const KEYWORD_METHOD = 'method';

    /**
     * Adds $advice to be run before pointcut
     * @param string
     * @return @this
     */
    public function addAdviceBefore($advice);

    /**
     * Adds $advice to be run after pointcut
     * @param string
     * @return @this
     */
    public function addAdviceAfter($advice);

    /**
     * Adds $advice to be run instead of pointcut and allow calling $invoke()
     * @param string
     * @return @this
     */
    public function addAdviceAround($advice);

    /**
     * Adds $advice to catch $exception when pointcut is invoked
     * @param string
     * @param string
     * @return @this
     */
    public function addExceptionAdvice($exception, $advice);

    /**
     * Weaves advices added by add*Advice* methods into the original method/function.
     * @return @this
     */
    public function weave();
}

/**
 * JoinPoint
 */
interface IJoinPoint {
    /**
     * JoinPoint Types
     */
    const TYPE_FUNCTION = 1;
    const TYPE_METHOD = 2;

    /**
     * Sets the aspect code to be called at the point
     * @param string
     * @return $this
     */
    public function setAdviceCode($adviceCode);

    /**
     * Weaves the point 
     * @param string
     * @return $this
     */
    public function weave();
}

/**
 * Weaver
 */
interface IWeaver {
    /**
     * weaved function name prefix and sufix
     */
    const PREFIX = 'YapaaWeaver_';
    const SUFIX = '_invokeOriginal';

    /**
     * returns name of the weaved function
     * @param string
     * @return string
     */
    public static function originalFunctionName($functionName);

    /**
     * Returns true if function is weaved
     * @param string
     * @return boolean
     */
    public static function isFunctionWeaved($functionName);

    /**
     * Returns true if method is weaved
     * @param string
     * @return boolean
     */
    public static function isMethodWeaved($className, $methodName);

    /**
     * Weave function
     * @param string
     * @param string
     * @return string
     */
    public static function weaveFunction($functionName, $adviceCode);

    /**
     * Weave method
     * @param string
     * @param string
     * @return string
     */
    public static function weaveMethod($className, $functionName, $adviceCode);
}
