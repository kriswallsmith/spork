<?php

/*
 * This file is part of Spork, an OpenSky project.
 *
 * (c) 2012 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spork;

use Spork\Batch\BatchJob;
use Spork\Batch\Strategy\StrategyInterface;
use Spork\Deferred\DeferredInterface;
use Spork\EventDispatcher\EventDispatcherInterface;
use Spork\EventDispatcher\Events;
use Spork\Exception\ProcessControlException;
use Spork\Exception\UnexpectedTypeException;

class ProcessManager
{
    private $dispatcher;
    private $forks;
    private $zombieOkay;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
        $this->forks = array();
        $this->zombieOkay = false;
    }

    public function __destruct()
    {
        if (!$this->zombieOkay) {
            $this->wait();
        }
    }

    public function getEventDispatcher()
    {
        return $this->dispatcher;
    }

    public function zombieOkay($zombieOkay = true)
    {
        $this->zombieOkay = $zombieOkay;
    }

    public function createBatchJob($data = null, StrategyInterface $strategy = null)
    {
        return new BatchJob($this, $data, $strategy);
    }

    public function process($data, $callable, StrategyInterface $strategy = null)
    {
        return $this->createBatchJob($data, $strategy)->execute($callable);
    }

    /**
     * Forks something into another process and returns a deferred object.
     */
    public function fork($callable)
    {
        if (!is_callable($callable)) {
            throw new UnexpectedTypeException($callable, 'callable');
        }

        $pid = pcntl_fork();

        if (-1 === $pid) {
            throw new ProcessControlException('Unable to fork a new process');
        }

        if (0 === $pid) {
            // reset the list of child processes
            $this->forks = array();

            // setup the fifo (blocks until parent connects)
            $fifo = new Fifo();

            // dispatch an event so the system knows it's in a new process
            $this->dispatcher->dispatch(Events::ON_FORK);

            ob_start();

            try {
                $result = call_user_func($callable);
                $exitStatus = is_integer($result) ? $result : 0;
                $error = null;
            } catch (\Exception $e) {
                $result = null;
                $exitStatus = 1;
                $error = array(
                    'class'   => get_class($e),
                    'message' => $e->getMessage(),
                    'code'    => $e->getCode(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                );
            }

            // phone home
            $fifo->send(array($result, ob_get_clean(), $error));
            $fifo->close();

            exit($exitStatus);
        }

        // connect to the fifo
        $fifo = new Fifo($pid);

        return $this->forks[] = new Fork($pid, $fifo);
    }

    public function wait($hang = true)
    {
        foreach ($this->forks as $fork) {
            if (DeferredInterface::STATE_PENDING !== $fork->getState()) {
                continue;
            }

            $fork->wait($hang);

            if ($fork->isExited()) {
                $fork->isSuccessful() ? $fork->resolve() : $fork->reject();
            }
        }
    }
}
