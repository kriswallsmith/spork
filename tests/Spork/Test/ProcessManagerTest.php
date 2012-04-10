<?php

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
