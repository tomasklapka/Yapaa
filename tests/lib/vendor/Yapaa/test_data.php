<?php

/**
 * This file is part of the Yapaa library - Yet another PHP AOP approach
 *
 * Copyright (c) 2011 Tomáš Klapka (tomas@klapka.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */
class TestClass {

    public function test_method($param) {
        return $param;
    }

    public static function test_static_method($param) {
        return $param;
    }

    public function test_method_weave($param) {
        return $param;
    }

    public function not_weaved($param) {
        return $param;
    }

    public static function not_weaved_static($param) {
        return $param;
    }

}

class TestClass1 {

    public function testClass1Method1($param) {
        return $param;
    }

    public function testClass1Method2($param) {
        return $param;
    }

}

class TestClass2 {

    public function testClass2Method1($param) {
        return $param;
    }

    public function testClass2Method2($param) {
        return $param;
    }

    public function hard_to_find_IDKFA_method($param) {
        return $param;
    }

}

function test_function($param) {
    return $param;
}

function hard_to_find_IDDQD_function($param) {
    return $param;
}

function test_function_weave($param) {
    return $param;
}

function test_function_around($param) {
    return $param;
}

function test_function_exception($param) {
    throw new Exception("just an exception");
    return $param;
}

function not_weaved($param) {
    return $param;
}