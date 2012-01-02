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

    public function getAdvices() {
        return $this->advices;
    }

    public function weave() {
        $this->findJoinPoints();
        foreach ($this->joinPoints as $joinPoint) {
            $joinPoint->buildAdvice();
            $joinPoint->weave();
        }
        return $this;
    }

    private function findJoinPoints() {
        $this->joinPoints = array();
        foreach ($this->masks as $mask) {
            list($type, $name, $class) = $this->parseMask($mask);
            if ($type === 'method') {
                $points = JoinPointMethod::findMatching($class, $name);
            } elseif ($type === 'function') {
                $points = JoinPointFunction::findMatching($name);
            }
            foreach ($points as $point) {
                array_push($this->joinPoints, $point->addPointcut($this));
            }
        }
        return $this->joinPoints;
    }

    private function parseMask($pointMask) {
        $class = '';
        $mask = preg_replace('/\s*/', '', $pointMask);
        if (preg_match('/^(\w+)\(([\\\\\w,\*_]+)\)$/', $mask, $match)) {
            list(, $type, $args) = $match;
            switch ($type) {
                case 'function':
                    $name = $args;
                    break;
                case 'method':
                    list($class, $name) = explode(',', $args);
                    break;
                default:
                    throw new YapaaException("Unknown pointcut type");
            }
        }
        return array($type, $name, $class);
    }

}
