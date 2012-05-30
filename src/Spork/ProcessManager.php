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

use Spork\Deferred\DeferredAggregate;
use Spork\Deferred\DeferredFactory;
use Spork\Deferred\DeferredInterface;
use Spork\Deferred\FactoryInterface;
use Spork\EventDispatcher\EventDispatcherInterface;
use Spork\EventDispatcher\Events;
use Spork\Exception\ProcessControlException;
use Spork\Exception\UnexpectedTypeException;

class ProcessManager
{
    private $dispatcher;
    private $factory;
    private $defers;

    public function __construct(EventDispatcherInterface $dispatcher, FactoryInterface $factory = null)
    {
        $this->dispatcher = $dispatcher;
        $this->factory = $factory ?: new DeferredFactory();
        $this->defers = array();

        $this->dispatcher->addSignalListener(SIGCHLD, array($this, 'waitNoHang'));
    }

    public function __clone()
    {
        $this->defers = array();

        $this->dispatcher->addSignalListener(SIGCHLD, array($this, 'waitNoHang'));
    }

    public function __destruct()
    {
        $this->wait();
    }

    public function getEventDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * Process each item in an iterator through a callable.
     */
    public function process(\Traversable $list, $callable)
    {
        if (!is_callable($callable)) {
            throw new UnexpectedTypeException($callable, 'callable');
        }

        $total = $list instanceof \Countable ? $list->count() : iterator_count($list);
        $forks = 3;
        $limit = ceil($total / $forks);

        $defers = array();
        for ($batch = 0; $batch < $forks; $batch++) {
            $min = $batch * $limit;
            $max = $min + $limit;

            $defers[] = $this->fork(function() use($list, $callable, $min, $max) {
                $cursor = 0;
                foreach ($list as $index => $element) {
                    if ($cursor >= $min) {
                        call_user_func($callable, $element, $index, $list);
                    }

                    if (++$cursor >= $max) {
                        break;
                    }
                }
            });
        }

        return $this->factory->createDeferredAggregate($defers);
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
            // reset the stack of defers
            $this->defers = array();

            // dispatch an event so the system knows it's in a new process
            $this->dispatcher->dispatch(Events::ON_FORK);

            ob_start();

            try {
                call_user_func($callable);
                $statusCode = 0;
            } catch (\Exception $e) {
                $statusCode = 1;
            }

            // dump the output to a file
            file_put_contents($this->getOutputFile(posix_getpid()), ob_get_clean());

            exit($statusCode);
        }

        return $this->defers[$pid] = $this->factory->createDeferred();
    }

    /**
     * Waits for all child processes to exit.
     */
    public function wait($hang = true)
    {
        foreach ($this->defers as $pid => $defer) {
            if (DeferredInterface::STATE_PENDING !== $defer->getState()) {
                continue;
            }

            $wait = pcntl_waitpid($pid, $status, $hang ? 0 : WNOHANG);

            if ($wait < 1) {
                continue;
            }

            if (file_exists($file = $this->getOutputFile($pid))) {
                $output = file_get_contents($file);
                unlink($file);
            } else {
                $output = null;
            }

            $statusCode = pcntl_wexitstatus($status);
            if (0 === $statusCode) {
                $defer->resolve($output, $statusCode, $status);
            } else {
                $defer->reject($output, $statusCode, $status);
            }
        }
    }

    public function waitNoHang()
    {
        $this->wait(false);
    }

    private function getOutputFile($pid)
    {
        return realpath(sys_get_temp_dir()).'/spork_'.$pid.'.out';
    }
}
