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

require_once __DIR__ . '/Pointcut.php';
require_once __DIR__ . '/JoinPoint.php';
require_once __DIR__ . '/RunkitWeaver.php';

/**
 * Implementation of \Yapaa\IYapaa
 * 
 * @author Tomáš Klapka
 */
class Yapaa implements IYapaa {

    private static $pointcuts = array();
    
    private function __construct() {
        /* deny access to constructor - static factory */
    }

    public static function Pointcut($pointcutMask) {
        $pointcut = new Pointcut($pointcutMask);
        static::$pointcuts[] = $pointcut;
        return $pointcut;
    }

    public static function weaveAllPointcuts() {
        foreach (static::$pointcuts as $pointcut) {
            $pointcut->weave();
        }
    }
}
