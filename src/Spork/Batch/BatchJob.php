<?php

/*
 * This file is part of Spork, an OpenSky project.
 *
 * (c) OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spork\Batch;

use Spork\Batch\Strategy\ChunkStrategy;
use Spork\Batch\Strategy\StrategyInterface;
use Spork\Exception\UnexpectedTypeException;
use Spork\ProcessManager;

class BatchJob
{
    private $manager;
    private $data;
    private $strategy;
    private $name;
    private $callback;

    public function __construct(ProcessManager $manager, $data = null, StrategyInterface $strategy = null)
    {
        $this->manager = $manager;
        $this->data = $data;
        $this->strategy = $strategy ?: new ChunkStrategy();
        $this->name = '<anonymous>';
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function setStrategy(StrategyInterface $strategy)
    {
        $this->strategy = $strategy;

        return $this;
    }

    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    public function setCallback($callback)
    {
        if (!is_callable($callback)) {
            throw new UnexpectedTypeException($callback, 'callable');
        }

        $this->callback = $callback;

        return $this;
    }

    public function execute($callback = null)
    {
        if (null !== $callback) {
            $this->setCallback($callback);
        }

        return $this->manager->fork($this)->setName($this->name.' batch');
    }

    /**
     * Runs in a child process.
     *
     * @see execute()
     */
    public function __invoke()
    {
        $forks = array();
        foreach ($this->strategy->createBatches($this->data) as $index => $batch) {
            $forks[] = $this->manager
                ->fork($this->strategy->createRunner($batch, $this->callback))
                ->setName(sprintf('%s batch #%d', $this->name, $index))
            ;
        }

        // block until all forks have exited
        $this->manager->wait();

        $results = array();
        foreach ($forks as $fork) {

            $exitStatus = $fork->getExitStatus();
            if (0 !== $exitStatus) {
                if ($error = $fork->getError()) {
                    // Unfortunately the original exception is lost and Error is not throwable
                    throw new \RuntimeException($error->getMessage());
                }

                return $exitStatus;
            }

            $results = array_merge($results, (array) $fork->getResult());
        }

        return $results;
    }
}
