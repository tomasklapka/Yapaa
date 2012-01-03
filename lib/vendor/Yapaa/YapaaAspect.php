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

/**
 * Abstract class for Aspects
 * 
 * @author Tomáš Klapka
 */
abstract class YapaaAspect {

    /**
     * Weaves all children methods annotated
     */
    final public static function weave() {
        self::findAspectsAndGeneratePointcuts();
        Yapaa::weaveAllPointcuts();
    }

    private static function findAspectsAndGeneratePointcuts() {
        $children = self::findAllChildren();
        foreach ($children as $child) {
            foreach (get_class_methods($child) as $method) {
                $r = new \ReflectionMethod($child, $method);
                $annotations = self::parseAnnotations($r->getDocComment());
                if (count($annotations) > 0) {
                    $adviceCode = "$child::$method" . '($className, $functionName, $argc, $argv);';
                    foreach ($annotations as $annotation) {
                        List($where, $mask, $exception) = $annotation;
                        $pointcut = Yapaa::Pointcut($mask);
                        self::addAdviceCodeToPointcutWhereException($adviceCode, $pointcut, $where, $exception);
                    }
                }
            }
        }
    }

    /**
     * @param string $aspectCode
     * @param \Yapaa\IPointcut $pointcut
     * @param string $where
     * @param string $exception 
     */
    private static function addAdviceCodeToPointcutWhereException($adviceCode, $pointcut, $where, $exception) {
        switch ($where) {
            case 'before':
                $pointcut->addAdviceBefore($adviceCode);
                break;
            case 'after':
                $pointcut->addAdviceAfter($adviceCode);
                break;
            case 'around':
                $pointcut->addAdviceAround($adviceCode);
                break;
            case 'exception':
                $pointcut->addExceptionAdvice($exception, $adviceCode);
        }
        return $pointcut;
    }

    private static function findAllChildren() {
        $children = array();
        $classes = get_declared_classes();
        foreach ($classes as $class) {
            if (is_subclass_of($class, __CLASS__)) {
                $children[] = $class;
            }
        }
        return $children;
    }

    private static function parseAnnotations($docComment) {
        $return = array();
        $lines = explode("\n", $docComment);
        foreach ($lines as $line) {
            if (preg_match('/^[\*\s]+@(.*)$/', $line, $matches)) {
                $find = array(
                    '/^aspect\((exception),([\w,\*\(\)]+),(\w+)\)$/',
                    '/^aspect\((\w+),([\w,\*\(\)]+)\)$/',
                );
                if ((preg_match($find[0], $matches[1], $aspect_matches)) or
                        ((preg_match($find[1], $matches[1], $aspect_matches)))) {
                    array_shift($aspect_matches);
                    if (count($aspect_matches) < 3) {
                        $aspect_matches[] = NULL;
                    }
                    $return[] = $aspect_matches;
                }
            }
        }
        return $return;
    }

}

