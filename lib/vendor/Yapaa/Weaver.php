<?php

/**
 * This file is part of the Yapaa library - Yet another PHP AOP approach
 *
 * Copyright (c) 2011 Tomáš Klapka (tomas@klapka.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Yappa;

use Yappa;

/**
 * Static factory for Function or Method Weaver
 * 
 * @author Tomáš Klapka
 */
interface Weaver {

    /**
     * Creates and returns Function Weaver for provided function
     * @param  string
     * @return \Yappa\WeaveFunction
     * @throws \Yappa\UndefinedFunctionException
     */
    public static function weaveFunction($functionName);

    /**
     * Creates and returns Function Weaver for provided method
     * @param  string
     * @return \Yappa\WeaveFunction
     * @throws \Yappa\UndefinedFunctionException
     */
    public static function weaveClassMethod($className, $methodName);
}

/**
 * Function Weaver
 * 
 * @author Tomáš Klapka
 */
interface WeaveFunction {

    /**
     * Adds code to be run before invokation of the function/method
     * @param  string
     */
    public function addCutBefore($adviceCode);

    /**
     * Adds code to be run after invokation of the function/method
     * @param  string
     */
    public function addCutAfter($adviceCode);

    /**
     * Adds $adviceCode to catch $exceptionName when invoking the function/method
     * @param  string
     * @param  string
     */
    public function addCutCatchException($exceptionName, $adviceCode);

    /**
     * Returns true if the function/method is already weaved
     * @return  boolean
     */
    public function isWeaved();

    /**
     * Weaves code added by addCut* methods into the original method/function.
     * This method can be used to reweave the method/function if new code is added.
     */
    public function weave();
}
