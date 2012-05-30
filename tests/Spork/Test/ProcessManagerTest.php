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

use Spork\EventDispatcher\EventDispatcher;
use Spork\ProcessManager;

class ProcessManagerTest extends \PHPUnit_Framework_TestCase
{
    private $manager;

    protected function setUp()
    {
        $this->manager = new ProcessManager(new EventDispatcher());
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

    public function testBatchProcessing()
    {
        $log = array();

        $list = new \ArrayIterator(range(100, 109));
        $this->manager->process($list, function($element, $index) {
            echo $element.' ';
        })->then(function($defer) use(& $log) {
            foreach ($defer->getChildren() as $child) {
                $child->always(function($output) use(& $log) {
                    $log[] = $output;
                });
            }
        });

        $this->manager->wait();

        $this->assertEquals(array(
            '100 101 102 103 ',
            '104 105 106 107 ',
            '108 109 ',
        ), $log);
    }
}
