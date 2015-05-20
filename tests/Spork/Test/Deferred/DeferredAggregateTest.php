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
use Spork\Deferred\DeferredAggregate;

class DeferredAggregateTest extends \PHPUnit_Framework_TestCase
{
    public function testInvalidChild()
    {
        $this->setExpectedException('Spork\Exception\UnexpectedTypeException', 'PromiseInterface');

        $defer = new DeferredAggregate(array('asdf'));
    }

    public function testNoChildren()
    {
        $defer = new DeferredAggregate(array());

        $log = array();
        $defer->done(function () use (& $log) {
            $log[] = 'done';
        });

        $this->assertEquals(array('done'), $log);
    }

    public function testResolvedChildren()
    {
        $child = new Deferred();
        $child->resolve();

        $defer = new DeferredAggregate(array($child));

        $log = array();
        $defer->done(function () use (& $log) {
            $log[] = 'done';
        });

        $this->assertEquals(array('done'), $log);
    }

    public function testResolution()
    {
        $child1 = new Deferred();
        $child2 = new Deferred();

        $defer = new DeferredAggregate(array($child1, $child2));

        $log = array();
        $defer->done(function () use (& $log) {
            $log[] = 'done';
        });

        $this->assertEquals(array(), $log);

        $child1->resolve();
        $this->assertEquals(array(), $log);

        $child2->resolve();
        $this->assertEquals(array('done'), $log);
    }

    public function testRejection()
    {
        $child1 = new Deferred();
        $child2 = new Deferred();
        $child3 = new Deferred();

        $defer = new DeferredAggregate(array($child1, $child2, $child3));

        $log = array();
        $defer->then(function () use (& $log) {
            $log[] = 'done';
        }, function () use (& $log) {
            $log[] = 'fail';
        });

        $this->assertEquals(array(), $log);

        $child1->resolve();
        $this->assertEquals(array(), $log);

        $child2->reject();
        $this->assertEquals(array('fail'), $log);

        $child3->resolve();
        $this->assertEquals(array('fail'), $log);
    }

    public function testNested()
    {
        $child1a = new Deferred();
        $child1b = new Deferred();
        $child1 = new DeferredAggregate(array($child1a, $child1b));
        $child2 = new Deferred();

        $defer = new DeferredAggregate(array($child1, $child2));

        $child1a->resolve();
        $child1b->resolve();
        $child2->resolve();

        $this->assertEquals('resolved', $defer->getState());
    }

    public function testFail()
    {
        $child = new Deferred();
        $defer = new DeferredAggregate(array($child));

        $log = array();
        $defer->fail(function () use (& $log) {
            $log[] = 'fail';
        });

        $child->reject();

        $this->assertEquals(array('fail'), $log);
    }
}
