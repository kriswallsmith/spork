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

use Spork\Batch\Strategy\StrategyInterface;
use Spork\EventDispatcher\EventDispatcher;
use Spork\EventDispatcher\EventDispatcherInterface;
use Spork\EventDispatcher\Events;
use Spork\Exception\ProcessControlException;
use Spork\Exception\UnexpectedTypeException;
use Spork\Util\Error;
use Spork\Util\ExitMessage;

class ProcessManager
{
    private $dispatcher;
    private $factory;
    private $debug;
    private $zombieOkay;
    private $signal;

    /** @var Fork[] */
    private $forks;

    public function __construct(EventDispatcherInterface $dispatcher = null, Factory $factory = null, $debug = false)
    {
        $this->dispatcher = $dispatcher ?: new EventDispatcher();
        $this->factory = $factory ?: new Factory();
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
        if (is_integer($eventName)) {
            $this->dispatcher->addSignalListener($eventName, $listener, $priority);
        } else {
            $this->dispatcher->addListener($eventName, $listener, $priority);
        }
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
        return $this->factory->createBatchJob($this, $data, $strategy);
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

            // setup the shared memory
            $shm = $this->factory->createSharedMemory(null, $this->signal);
            $message = new ExitMessage();

            // phone home on shutdown
            register_shutdown_function(function () use ($shm, $message) {
                $status = null;

                try {
                    $shm->send($message, false);
                } catch (\Exception $e) {
                    // probably an error serializing the result
                    $message->setResult(null);
                    $message->setError(Error::fromException($e));

                    $shm->send($message, false);

                    exit(2);
                }
            });

            // dispatch an event so the system knows it's in a new process
            $this->dispatcher->dispatch(Events::POST_FORK);

            if (!$this->debug) {
                ob_start();
            }

            try {
                $result = call_user_func($callable, $shm);

                $message->setResult($result);
                $status = is_integer($result) ? $result : 0;
            } catch (\Exception $e) {
                $message->setError(Error::fromException($e));
                $status = 1;
            }

            if (!$this->debug) {
                $message->setOutput(ob_get_clean());
            }

            exit($status);
        }

        // connect to shared memory
        $shm = $this->factory->createSharedMemory($pid);

        return $this->forks[$pid] = $this->factory->createFork($pid, $shm, $this->debug);
    }

    public function monitor($signal = SIGUSR1)
    {
        $this->signal = $signal;
        $this->dispatcher->addSignalListener($signal, array($this, 'check'));
    }

    public function check()
    {
        foreach ($this->forks as $fork) {
            foreach ($fork->receive() as $message) {
                $fork->notify($message);
            }
        }
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
