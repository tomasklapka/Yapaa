<?php

/**
 * This file is part of the Yapaa library - Yet another PHP AOP approach
 *
 * Copyright (c) 2012 Tomáš Klapka (tomas@klapka.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Yapaa;

require_once __DIR__ . '/../../../../lib/vendor/Yapaa/YapaaAspect.php';

/**
 * Abstract class for Aspects
 * 
 * @author Tomáš Klapka
 */
class TestClassAspect extends YapaaAspect {

    private static $logFilename = NULL;
    private static $counter = 0;

    private static function log($message) {
        if (self::$logFilename === NULL) {
            self::$logFilename = './yapaaAspectTest.log';
        }
        if (!file_exists(self::$logFilename)) {
            fclose(fopen(self::$logFilename, "w"));
        }
        file_put_contents(self::$logFilename, date('Y-m-d H:i:s') . ": $message\n", FILE_APPEND | LOCK_EX);
    }

    public static function getCounter() {
        return self::$counter;
    }

    /**
     * This aspect is logging method calls
     * @aspect(after,method(TestClass,*))
     */
    public static function logTestClass($className, $functionName, $argc, $argv) {
        self::$counter++;
        $message = "called $className::$functionName with $argc arguments: " . join(', ', $argv);
        self::log($message);
    }

}
