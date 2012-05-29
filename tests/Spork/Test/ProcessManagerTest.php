<?php

/*
 * This file is part of Spork, an OpenSky project.
 *
 * (c) 2012 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spork\Test;

use Spork\Deferred\DeferredFactory;
use Spork\ProcessManager;

class ProcessManagerTest extends \PHPUnit_Framework_TestCase
{
    private $manager;

    protected function setUp()
    {
        $this->manager = new ProcessManager(new DeferredFactory());
    }

    protected function tearDown()
    {
        unset($this->manager);
    }

    public function testDoneCallbacks()
    {
        $log = array();

        $this->manager->fork(function() use(& $log) {
            echo 'child';
        })->always(function($output, $status) use(& $log) {
            $log[] = $output;
        })->done(function($output, $status) use(& $log) {
            $log[] = 'done';
        })->fail(function($output, $status) use(& $log) {
            $log[] = 'fail';
        });

        $this->manager->wait();

        $this->assertEquals(array('child', 'done'), $log);
    }

    public function testFailCallbacks()
    {
        $log = array();

        $this->manager->fork(function() use(& $log) {
            throw new \Exception('child fail');
        })->always(function($output, $status) use(& $log) {
            $log[] = $output;
        })->done(function($output, $status) use(& $log) {
            $log[] = 'done';
        })->fail(function($output, $status) use(& $log) {
            $log[] = 'fail';
        });

        $this->manager->wait();

        $this->assertEquals(array('', 'fail'), $log);
    }
}
