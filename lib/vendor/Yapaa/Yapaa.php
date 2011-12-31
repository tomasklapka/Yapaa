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

    public function __construct() {
        throw new YapaaException("Yapaa is static class. It cannot be instantiated.");
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
    
    public function __clone() {
        throw new YapaaException("Yapaa is static class. It cannot be cloned.");
    }
    
    public function __wakeup() {
        throw new YapaaException("Yapaa is static class. It cannot be unserialized.");
    }

}

/**
 * Yapaa Exception
 * 
 * @author Tomáš Klapka
 */
class YapaaException extends \RuntimeException {
    
}