<?php

/*
 * This file is part of Spork, an OpenSky project.
 *
 * (c) OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spork;

use Spork\Batch\BatchJob;
use Spork\Batch\Strategy\StrategyInterface;

class Factory
{
    /**
     * Creates a new batch job instance.
     *
     * @param ProcessManager    $manager  The process manager
     * @param null              $data     Data for the batch job
     * @param StrategyInterface $strategy The strategy
     *
     * @return BatchJob A new batch job instance
     */
    public function createBatchJob(ProcessManager $manager, $data = null, StrategyInterface $strategy = null)
    {
        return new BatchJob($manager, $data, $strategy);
    }

    /**
     * Creates a new shared memory instance.
     *
     * @param integer $pid    The child process id or null if this is the child
     * @param integer $signal The signal to send after writing to shared memory
     *
     * @return SharedMemory A new shared memory instance
     */
    public function createSharedMemory($pid = null, $signal = null)
    {
        return new SharedMemory($pid, $signal);
    }

    /**
     * Creates a new fork instance.
     *
     * @param int          $pid   Process id
     * @param SharedMemory $shm   Shared memory
     * @param bool         $debug Debug mode
     *
     * @return Fork A new fork instance
     */
    public function createFork($pid, SharedMemory $shm, $debug = false)
    {
        return new Fork($pid, $shm, $debug);
    }
}
