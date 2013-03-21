<?php

/*
 * This file is part of Spork, an OpenSky project.
 *
 * (c) OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spork\Test\Batch\Strategy;

use Spork\Batch\Strategy\MongoStrategy;
use Spork\EventDispatcher\EventDispatcher;
use Spork\EventDispatcher\Events;
use Spork\ProcessManager;

class MongoStrategyTest extends \PHPUnit_Framework_TestCase
{
    private $mongo;
    private $manager;

    protected function setUp()
    {
        if (!extension_loaded('mongo')) {
            $this->markTestSkipped('Mongo extension is not loaded');
        }

        try {
            $this->mongo = new \Mongo();
        } catch (\MongoConnectionException $e) {
            $this->markTestSkipped($e->getMessage());
        }

        $this->manager = new ProcessManager();
        $this->manager->setDebug(true);

        // close the connection prior to forking
        $this->manager->addListener(Events::PRE_FORK, array($this->mongo, 'close'));
    }

    protected function tearDown()
    {
        if ($this->mongo) {
            $this->mongo->close();
        }

        unset($this->mongo, $this->manager);
    }

    public function testBatchJob()
    {
        $coll = $this->mongo->spork->widgets;

        $coll->remove();
        $coll->batchInsert(array(
            array('name' => 'Widget 1'),
            array('name' => 'Widget 2'),
            array('name' => 'Widget 3'),
        ));

        $this->manager->createBatchJob($coll->find(), new MongoStrategy())
            ->execute(function($doc) use($coll) {
                $coll->update(
                    array('_id' => $doc['_id']),
                    array('$set' => array('seen' => true)),
                    array('safe' => true)
                );
            });

        $this->manager->wait();

        foreach ($coll->find() as $doc) {
            $this->assertArrayHasKey('seen', $doc);
        }
    }
}
