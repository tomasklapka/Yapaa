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
 * Implementation of \Yapaa\IPointcut
 * 
 * @author Tomáš Klapka
 */
class Pointcut implements IPointcut {

    private $masks = array();
    private $advices = array(
        'before' => array(),
        'after' => array(),
        'around' => array(),
        'exception' => array(),
    );
    private $joinPoints = array();

    public function __construct($masks) {
        if (is_array($masks)) {
            $this->masks += $masks;
        } else {
            $this->masks[] = $masks;
        }
    }

    private function addAdvice($where, $advice, $key = NULL) {
        if ($key === NULL) {
            $this->advices[$where][] = $advice;
        } else {
            $advices = &$this->advices[$where];
            if (!isset($advices[$key])) {
                $advices[$key] = array();
            }
            $advices[$key][] = $advice;
        }
        $this->weave();
        return $this;
    }

    public function addAdviceBefore($advice) {
        $this->addAdvice('before', $advice);
        return $this;
    }

    public function addAdviceAfter($advice) {
        $this->addAdvice('after', $advice);
        return $this;
    }

    public function addAdviceAround($advice) {
        $this->addAdvice('around', $advice);
        return $this;
    }

    public function addExceptionAdvice($exception, $advice) {
        $this->addAdvice('exception', $advice, $exception);
        return $this;
    }

    public function weave() {
        $this->findJoinPoints();
        foreach ($this->joinPoints as $joinPoint) {
            $joinPoint->setAdviceCode($this->buildAdvice($joinPoint));
            $joinPoint->weave();
        }
        return $this;
    }

    private function findJoinPoints() {
        $this->joinPoints = array();
        foreach ($this->masks as $mask) {
            list($type, $name, $class) = $this->parseMask($mask);
            //var_dump ($type, $name, $class);
            if ($type === 'method') {
                $points = JoinPointMethod::findMatching($class, $name);
            } elseif ($type === 'function') {
                $points = JoinPointFunction::findMatching($name);
            }
            foreach ($points as $point) {
                array_push($this->joinPoints, $point);
            }
        }
        return $this->joinPoints;
    }

    private function parseMask($pointMask) {
        $class = '';
        $mask = preg_replace('/\s*/', '', $pointMask);
        if (preg_match('/^(\w+)\(([\w,\*_]+)\)$/', $mask, $match)) {
            list(, $type, $args) = $match;
            switch ($type) {
                case 'function':
                    $name = $args;
                    break;
                case 'method':
                    list($class, $name) = explode(',', $args);
                    break;
                default:
                    throw new Exception("Unknown pointcut type");
            }
        }
        return array($type, $name, $class);
    }

    private function buildAdvice($joinPoint) {

        $new_func_ident = $this->buildFunctionIdentString($joinPoint);
        $new_func_call = "\$return .= call_user_func_array($new_func_ident, ((\$argc > 0) ? \$argv : NULL));";
        list($exceptionsBegin, $exceptionsEnd) = $this->buildExceptionsAdviceString();
        $codeBefore = $this->buildBeforeAdviceString();
        $codeAround = $this->buildAroundAdviceString($new_func_call);
        $codeAfter = $this->buildAfterAdviceString();

        $advice = '
                $argc = func_num_args();
                $argv = func_get_args();
                $return = NULL;' . "\n" .
                $codeBefore . ";\n" .
                $exceptionsBegin . "\n" .
                $codeAround . ";\n" .
                $exceptionsEnd . "\n" .
                $codeAfter . ";\n" .
                'return $return' . ";\n";

//        var_dump ($advice);
        return $advice;
    }

    private function buildFunctionIdentString($joinPoint) {
        if ($joinPoint->getType() == IJoinPoint::TYPE_FUNCTION) {
            $func_ident = "'" . $joinPoint->getOriginalFunctionName() . "'";
        } else {
            $className = $joinPoint->getClassName();
            $reflection = new \ReflectionMethod($className, $joinPoint->getMethodName());
            if ($reflection->isStatic()) {
                $func_ident = "'" . $className . "::" . $joinPoint->getOriginalFunctionName() . "'";
            } else {
                $func_ident = "array('" . $className . "', '" . $joinPoint->getOriginalFunctionName() . "')";
            }
        }
        return $func_ident;
    }

    private function buildExceptionsAdviceString() {
        $string = '';
        foreach ($this->advices['exception'] as $exception => $advices) {
            $advice = join("; ", $advices);
            $string .= "catch ($exception \$e) { $advice; }\n";
        }
        if (strlen($string) > 0) {
            return array("try {\n", "\n} $string");
        }
        return array('', '');
    }

    private function buildBeforeAdviceString() {
        return implode(";\n", $this->advices['before']);
    }

    private function buildAfterAdviceString() {
        return implode(";\n", $this->advices['after']);
    }

    private function buildAroundAdviceString($new_func_call) {
        $string = $new_func_call;
        foreach ($this->advices['around'] as $aroundAdvice) {
            $string = str_replace(self::KEYWORD_PROCEED, "$string;", $aroundAdvice);
        }
        return $string;
    }

}
