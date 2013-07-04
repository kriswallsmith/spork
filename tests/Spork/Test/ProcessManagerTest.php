<?php

/*
 * This file is part of Spork, an OpenSky project.
 *
 * (c) OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spork\Test;

use Spork\EventDispatcher\EventDispatcher;
use Spork\ProcessManager;

class ProcessManagerTest extends \PHPUnit_Framework_TestCase
{
    private $manager;

    protected function setUp()
    {
        $this->manager = new ProcessManager();
    }

    protected function tearDown()
    {
        unset($this->manager);
    }

    public function testDoneCallbacks()
    {
        $success = null;

        $fork = $this->manager->fork(function() {
            echo 'output';
            return 'result';
        })->done(function() use(& $success) {
            $success = true;
        })->fail(function() use(& $success) {
            $success = false;
        });

        $this->manager->wait();

        $this->assertTrue($success);
        $this->assertEquals('output', $fork->getOutput());
        $this->assertEquals('result', $fork->getResult());
    }

    public function testForkWithArguments()
    {
        $fork = $this->manager->fork(function ($fifo, $argument) {
            return $argument;
        }, 'hello');

        $fork->wait();

        $this->assertEquals('hello', $fork->getResult());
    }

    public function testFailCallbacks()
    {
        $success = null;

        $fork = $this->manager->fork(function() {
            throw new \Exception('child error');
        })->done(function() use(& $success) {
            $success = true;
        })->fail(function() use(& $success) {
            $success = false;
        });

        $this->manager->wait();

        $this->assertFalse($success);
        $this->assertNotEmpty($fork->getError());
    }

    public function testBatchProcessing()
    {
        $expected = range(100, 109);

        $fork = $this->manager->process($expected, function($item) {
            return $item;
        });

        $this->manager->wait();

        $this->assertEquals($expected, $fork->getResult());
    }
}
