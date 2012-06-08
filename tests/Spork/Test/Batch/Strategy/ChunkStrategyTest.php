<?php

/*
 * This file is part of Spork, an OpenSky project.
 *
 * (c) 2012 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spork\Test\Batch\Strategy;

use Spork\Batch\Strategy\ChunkStrategy;

class ChunkStrategyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provideNumber
     */
    public function testChunkArray($number, $expectedCounts)
    {
        $strategy = new ChunkStrategy($number);
        $batches = $strategy->createBatches(range(1, 100));

        $this->assertEquals(count($expectedCounts), count($batches));
        foreach ($batches as $i => $batch) {
            $this->assertCount($expectedCounts[$i], $batch);
        }
    }

    public function provideNumber()
    {
        return array(
            array(1, array(100)),
            array(2, array(50, 50)),
            array(3, array(34, 34, 32)),
            array(4, array(25, 25, 25, 25)),
            array(5, array(20, 20, 20, 20, 20)),
        );
    }
}
