<?php

/**
 * This file is part of the Yapaa library - Yet another PHP AOP approach
 *
 * Copyright (c) 2011 Tomáš Klapka (tomas@klapka.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

require_once dirname(__FILE__) . '/../../../../lib/vendor/Yapaa/Yapaa.php';

class TestClass {

    public function testFunc($var1, $var2) {
        return "testFunc($var1, $var2)\n";
    }
}

function test_function($var1, $var2) {
    return "test_function($var1, $var2)\n";
}

class Test1Exception extends \Exception {}
class Test2Exception extends \Exception {}

class YapaaTest extends \PHPUnit_Framework_TestCase {

    protected $testObj;

    protected function setUp() {
        $this->testObj = new TestClass();
    }

    public function testWeave() {
        $fn    = \Yapaa\Yapaa::weaveFunction('test_function');
        $mthd  = \Yapaa\Yapaa::weaveClassMethod('TestClass', 'testFunc');
        $phpfn = \Yapaa\Yapaa::weaveFunction('pow');

        foreach (array($fn, $mthd, $phpfn) as $obj) {
            $obj->addCutBefore('$return .= "!before1!\n"');
            $obj->addCutBefore('$return .= "!before2!\n"');
            $obj->addCutCatchException('\Test1Exception', '$return .= "!Test1Exception!\n"');
            $obj->addCutCatchException('\Test2Exception', '$return .= "!Test2Exception!\n"');
            $obj->addCutAfter('$return .= "!after1!\n"');
            $obj->addCutAfter('$return .= "!after2!\n"');
            $obj->weave();
        }
        
        $this->assertEquals($this->testObj->testFunc(1, 2), '!before1!
!before2!
testFunc(1, 2)
!after1!
!after2!
');
        $this->assertEquals(test_function(1, 2), '!before1!
!before2!
test_function(1, 2)
!after1!
!after2!
');
        // to weave PHP internal function it is required to set PHP_INI_SYSTEM setting runkit.internal_override=1
        $this->assertEquals(pow(3, 2), '!before1!
!before2!
9!after1!
!after2!
');
    }

}
