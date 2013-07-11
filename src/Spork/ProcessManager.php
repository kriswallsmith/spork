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
use Spork\Deferred\DeferredInterface;
use Spork\EventDispatcher\EventDispatcher;
use Spork\EventDispatcher\EventDispatcherInterface;
use Spork\EventDispatcher\Events;
use Spork\Exception\ProcessControlException;
use Spork\Exception\UnexpectedTypeException;

class ProcessManager
{
    private $dispatcher;
    private $debug;
    private $zombieOkay;
    private $forks;

    public function __construct(EventDispatcherInterface $dispatcher = null, $debug = false)
    {
        $this->dispatcher = $dispatcher ?: new EventDispatcher();
        $this->debug = $debug;
        $this->zombieOkay = false;
        $this->forks = array();
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

    public function addListener($eventName, $listener, $priority = 0)
    {
        $this->dispatcher->addListener($eventName, $listener, $priority);
    }

    public function setDebug($debug)
    {
        $this->debug = $debug;
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

        // allow the system to cleanup before forking
        $this->dispatcher->dispatch(Events::PRE_FORK);

        if (-1 === $pid = pcntl_fork()) {
            throw new ProcessControlException('Unable to fork a new process');
        }

        if (0 === $pid) {
            // reset the list of child processes
            $this->forks = array();

            // setup the fifo (blocks until parent connects)
            $fifo = new Fifo();

            // dispatch an event so the system knows it's in a new process
            $this->dispatcher->dispatch(Events::POST_FORK);

            ob_start();

            try {
                $result = call_user_func($callable, $fifo);
                $exitStatus = is_integer($result) ? $result : 0;
                $error = null;
            } catch (\Exception $e) {
                $result = null;
                $exitStatus = 1;
                $error = array(
                    get_class($e),
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine(),
                    $e->getCode(),
                );
            }

            $output = ob_get_contents();
            $this->debug ? ob_end_flush() : ob_end_clean();

            // phone home
            try {
                $fifo->send(array($result, $output, $error));
            } catch (\Exception $e) {
                // probably an error serializing the result
                $result = null;
                $exitStatus = 1;
                $fifo->send(array($result, $output, array(
                    get_class($e),
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine(),
                    $e->getCode(),
                )));
            }

            $fifo->close();

            exit($exitStatus);
        }

        // connect to the fifo
        $fifo = new Fifo($pid);

        return $this->forks[$pid] = new Fork($pid, $fifo, $this->debug);
    }

    public function wait($hang = true)
    {
        foreach ($this->forks as $fork) {
            $fork->wait($hang);
        }
    }

    public function waitForNext($hang = true)
    {
        if (-1 === $pid = pcntl_wait($status, ($hang ? WNOHANG : 0) | WUNTRACED)) {
            throw new ProcessControlException('Error while waiting for next fork to exit');
        }

        if (isset($this->forks[$pid])) {
            $this->forks[$pid]->processWaitStatus($status);

            return $this->forks[$pid];
        }
    }

    public function waitFor($pid, $hang = true)
    {
        if (!isset($this->forks[$pid])) {
            throw new \InvalidArgumentException('There is no fork with PID '.$pid);
        }

        return $this->forks[$pid]->wait($hang);
    }

    /**
     * Sends a signal to all forks.
     */
    public function killAll($signal = SIGINT)
    {
        foreach ($this->forks as $fork) {
            $fork->kill($signal);
        }
    }
}
