<?php

/*
 * This file is part of Spork, an OpenSky project.
 *
 * (c) OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spork\Test\Deferred;

use Spork\Deferred\Deferred;

class DeferredTest extends \PHPUnit_Framework_TestCase
{
    private $defer;

    protected function setUp()
    {
        $this->defer = new Deferred();
    }

    protected function tearDown()
    {
        unset($this->defer);
    }

    /**
     * @dataProvider getMethodAndKey
     */
    public function testCallbackOrder($method, $expected)
    {
        $log = array();

        $this->defer->always(function() use(& $log) {
            $log[] = 'always';
            $log[] = func_get_args();
        })->done(function() use(& $log) {
            $log[] = 'done';
            $log[] = func_get_args();
        })->fail(function() use(& $log) {
            $log[] = 'fail';
            $log[] = func_get_args();
        });

        $this->defer->$method(1, 2, 3);

        $this->assertEquals(array(
            'always',
            array(1, 2, 3),
            $expected,
            array(1, 2, 3),
        ), $log);
    }

    /**
     * @dataProvider getMethodAndKey
     */
    public function testThen($method, $expected)
    {
        $log = array();

        $this->defer->then(function() use(& $log) {
            $log[] = 'done';
        }, function() use(& $log) {
            $log[] = 'fail';
        });

        $this->defer->$method();

        $this->assertEquals(array($expected), $log);
    }

    /**
     * @dataProvider getMethod
     */
    public function testMultipleResolve($method)
    {
        $log = array();

        $this->defer->always(function() use(& $log) {
            $log[] = 'always';
        });

        $this->defer->$method();
        $this->defer->$method();

        $this->assertEquals(array('always'), $log);
    }

    /**
     * @dataProvider getMethodAndInvalid
     */
    public function testInvalidResolve($method, $invalid)
    {
        $this->setExpectedException('LogicException', 'that has already been');

        $this->defer->$method();
        $this->defer->$invalid();
    }

    /**
     * @dataProvider getMethodAndQueue
     */
    public function testAlreadyResolved($resolve, $queue, $expect = true)
    {
        // resolve the object
        $this->defer->$resolve();

        $log = array();
        $this->defer->$queue(function() use(& $log, $queue) {
            $log[] = $queue;
        });

        $this->assertEquals($expect ? array($queue) : array(), $log);
    }

    /**
     * @dataProvider getMethodAndInvalidCallback
     */
    public function testInvalidCallback($method, $invalid)
    {
        $this->setExpectedException('Spork\Exception\UnexpectedTypeException', 'callable');

        $this->defer->$method($invalid);
    }

    // providers

    public function getMethodAndKey()
    {
        return array(
            array('resolve', 'done'),
            array('reject', 'fail'),
        );
    }

    public function getMethodAndInvalid()
    {
        return array(
            array('resolve', 'reject'),
            array('reject', 'resolve'),
        );
    }

    public function getMethodAndQueue()
    {
        return array(
            array('resolve', 'always'),
            array('resolve', 'done'),
            array('resolve', 'fail', false),
            array('reject', 'always'),
            array('reject', 'done', false),
            array('reject', 'fail'),
        );
    }

    public function getMethodAndInvalidCallback()
    {
        return array(
            array('always', 'foo!'),
            array('always', array('foo!')),
            array('done', 'foo!'),
            array('done', array('foo!')),
            array('fail', 'foo!'),
            array('fail', array('foo!')),
        );
    }

    public function getMethod()
    {
        return array(
            array('resolve'),
            array('reject'),
        );
    }
}
