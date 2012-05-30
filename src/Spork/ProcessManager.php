<?php

/*
 * This file is part of Spork, an OpenSky project.
 *
 * (c) 2012 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(ticks=1);

namespace Spork;

use Spork\Deferred\DeferredAggregate;
use Spork\Deferred\DeferredFactory;
use Spork\Deferred\DeferredInterface;
use Spork\Deferred\FactoryInterface;
use Spork\Exception\ProcessControlException;
use Spork\Exception\UnexpectedTypeException;

class ProcessManager
{
    private $factory;
    private $defers;

    public function __construct(FactoryInterface $factory = null)
    {
        $this->factory = $factory ?: new DeferredFactory();
        $this->defers = array();

        pcntl_signal(SIGCHLD, array($this, 'waitNoHang'));
    }

    public function __clone()
    {
        $this->defers = array();
    }

    public function __destruct()
    {
        $this->wait();
    }

    /**
     * Process each item in an iterator through a callable.
     */
    public function process(\Traversable $list, $callable, array $arguments = array())
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

            $defers[] = $this->fork(function() use($list, $callable, $arguments, $min, $max) {
                $cursor = 0;
                foreach ($list as $index => $element) {
                    if ($cursor >= $min) {
                        call_user_func_array($callable, array_merge(array($element, $index, $list), $arguments));
                    }

                    if (++$cursor >= $max) {
                        break;
                    }
                }
            });
        }

        return new DeferredAggregate($defers);
    }

    /**
     * Forks something into another process and returns a deferred object.
     */
    public function fork($callable, array $arguments = array())
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

            ob_start();

            try {
                call_user_func_array($callable, $arguments);
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
