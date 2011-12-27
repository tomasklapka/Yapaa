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

require_once __DIR__ . '/test_data.php';
require_once __DIR__ . '/../../../../lib/vendor/Yapaa/Pointcut.php';
require_once __DIR__ . '/../../../../lib/vendor/Yapaa/JoinPoint.php';
require_once __DIR__ . '/../../../../lib/vendor/Yapaa/RunkitWeaver.php';

/**
 * Test class for Pointcut.
 * Generated by PHPUnit on 2011-12-27 at 19:42:33.
 */
class PointcutTest extends \PHPUnit_Framework_TestCase {

    /**
     * Reads a private property from object
     * @param string
     * @param string
     * @param string
     * @return mixed
     */
    private function getPrivateValue($class, $property, $object) {
        $reflectionClass = new \ReflectionClass($class);
        $reflectionProperty = $reflectionClass->getProperty($property);
        $reflectionProperty->setAccessible(true);
        return $reflectionProperty->getValue($object);
    }

    /**
     * @covers \Yapaa\Pointcut::addAdviceAfter
     * @covers \Yapaa\Pointcut::weave
     */
    public function testAddAdviceAfter() {
        $pointcut = new Pointcut(array('function(test_function)', 'method(TestClass,*method)'));
        $advice = '$return .= "after";';
        $pointcut->addAdviceAfter($advice);
        $advices = $this->getPrivateValue('\Yapaa\Pointcut', 'advices', $pointcut);
        $this->assertEquals(1, count($advices['after']));
        $this->assertEquals($advice, $advices['after'][0]);
        $this->assertEquals("test1after", test_function('test1'));
        $object = new \TestClass();
        $this->assertEquals("test2after", $object->test_method('test2'));
        $this->assertEquals("test3after", \TestClass::test_static_method('test3'));
    }

    /**
     * @covers \Yapaa\Pointcut::addAdviceBefore
     * @covers \Yapaa\Pointcut::weave
     */
    public function testAddAdviceBefore() {
        $pointcut = new Pointcut('method(*,testClass*)');
        $advice = '$return .= "before";';
        $pointcut->addAdviceBefore($advice);
        $advices = $this->getPrivateValue('\Yapaa\Pointcut', 'advices', $pointcut);
        $this->assertEquals(1, count($advices['before']));
        $this->assertEquals($advice, $advices['before'][0]);
        $object1 = new \TestClass1();
        $object2 = new \TestClass2();
        $this->assertEquals("beforetest1", $object1->testClass1Method1('test1'));
        $this->assertEquals("beforetest2", $object2->testClass2Method2('test2'));
    }

    /**
     * @covers \Yapaa\Pointcut::addAdviceAround
     * @covers \Yapaa\Pointcut::weave
     */
    public function testAddAdviceAround() {
        $pointcut = new Pointcut('function(test_function_around)');
        $advice = '$return .= "before"; Yapaa::proceed(); $return .= "after";';
        $pointcut->addAdviceAround($advice);
        $advices = $this->getPrivateValue('\Yapaa\Pointcut', 'advices', $pointcut);
        $this->assertEquals(1, count($advices['around']));
        $this->assertEquals($advice, $advices['around'][0]);
        $this->assertEquals("beforetestafter", test_function_around('test'));
    }

    /**
     * @covers \Yapaa\Pointcut::addExceptionAdvice
     * @covers \Yapaa\Pointcut::weave
     */
    public function testAddExceptionAdvice() {
        $pointcut = new Pointcut('function(test_function_exception)');
        $advice = 'return "Exception thrown!";';
        $pointcut->addExceptionAdvice('Exception', $advice);
        $advices = $this->getPrivateValue('\Yapaa\Pointcut', 'advices', $pointcut);
        $this->assertEquals(1, count($advices['exception']['Exception']));
        $this->assertEquals($advice, $advices['exception']['Exception'][0]);
        $this->assertEquals("Exception thrown!", test_function_exception('test'));
    }

}