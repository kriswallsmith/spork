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

use Spork\Deferred\Deferred;
use Spork\Deferred\DeferredInterface;

class Fork implements DeferredInterface
{
    private $defer;
    private $pid;
    private $status;

    public function __construct($pid)
    {
        $this->defer = new Deferred();
        $this->pid = $pid;
    }

    public function getPid()
    {
        return $this->pid;
    }

    /**
     * Wait for the process to exit.
     *
     * @param Boolean $hang Whether to wait or exit immediately
     *
     * @return Boolean Returns true if the process has exited
     */
    public function wait($hang = true)
    {
        return $this->pid === pcntl_waitpid($this->pid, $this->status, ($hang ? 0 : WNOHANG) | WUNTRACED);
    }

    public function isExited()
    {
        return null !== $this->status && pcntl_wifexited($this->status);
    }

    public function isStopped()
    {
        return null !== $this->status && pcntl_wifstopped($this->status);
    }

    public function isSignaled()
    {
        return null !== $this->status && pcntl_wifsignaled($this->status);
    }

    public function getExitStatus()
    {
        if (null !== $this->status) {
            return pcntl_wexitstatus($this->status);
        }
    }

    public function getTermSignal()
    {
        if (null !== $this->status) {
            return pcntl_wtermsig($this->status);
        }
    }

    public function getStopSignal()
    {
        if (null !== $this->status) {
            return pcntl_wstopsig($this->status);
        }
    }

    public function getState()
    {
        return $this->defer->getState();
    }

    public function always($callback)
    {
        $this->defer->always($callback);

        return $this;
    }

    public function done($callback)
    {
        $this->defer->done($callback);

        return $this;
    }

    public function fail($callback)
    {
        $this->defer->fail($callback);

        return $this;
    }

    public function then($done, $fail = null)
    {
        $this->defer->then($done, $fail);

        return $this;
    }

    public function resolve()
    {
        $args = func_get_args();
        call_user_func_array(array($this->defer, 'resolve'), $args);

        return $this;
    }

    public function reject()
    {
        $args = func_get_args();
        call_user_func_array(array($this->defer, 'reject'), $args);

        return $this;
    }
}
