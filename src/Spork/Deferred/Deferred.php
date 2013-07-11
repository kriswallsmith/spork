<?php

/*
 * This file is part of Spork, an OpenSky project.
 *
 * (c) OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spork\Deferred;

use Spork\Exception\UnexpectedTypeException;

class Deferred implements DeferredInterface
{
    private $state;
    private $progressCallbacks;
    private $alwaysCallbacks;
    private $doneCallbacks;
    private $failCallbacks;
    private $callbackArgs;

    public function __construct()
    {
        $this->state = DeferredInterface::STATE_PENDING;

        $this->progressCallbacks = array();
        $this->alwaysCallbacks = array();
        $this->doneCallbacks = array();
        $this->failCallbacks = array();
    }

    public function getState()
    {
        return $this->state;
    }

    public function progress($progress)
    {
        if (!is_callable($progress)) {
            throw new UnexpectedTypeException($progress, 'callable');
        }

        $this->progressCallbacks[] = $progress;

        return $this;
    }

    public function always($always)
    {
        if (!is_callable($always)) {
            throw new UnexpectedTypeException($always, 'callable');
        }

        switch ($this->state) {
            case DeferredInterface::STATE_PENDING:
                $this->alwaysCallbacks[] = $always;
                break;
            default:
                call_user_func_array($always, $this->callbackArgs);
                break;
        }

        return $this;
    }

    public function done($done)
    {
        if (!is_callable($done)) {
            throw new UnexpectedTypeException($done, 'callable');
        }

        switch ($this->state) {
            case DeferredInterface::STATE_PENDING:
                $this->doneCallbacks[] = $done;
                break;
            case DeferredInterface::STATE_RESOLVED:
                call_user_func_array($done, $this->callbackArgs);
        }

        return $this;
    }

    public function fail($fail)
    {
        if (!is_callable($fail)) {
            throw new UnexpectedTypeException($fail, 'callable');
        }

        switch ($this->state) {
            case DeferredInterface::STATE_PENDING:
                $this->failCallbacks[] = $fail;
                break;
            case DeferredInterface::STATE_REJECTED:
                call_user_func_array($fail, $this->callbackArgs);
                break;
        }

        return $this;
    }

    public function then($done, $fail = null)
    {
        $this->done($done);

        if ($fail) {
            $this->fail($fail);
        }

        return $this;
    }

    public function notify()
    {
        if (DeferredInterface::STATE_PENDING !== $this->state) {
            throw new \LogicException('Cannot notify a deferred object that is no longer pending');
        }

        $args = func_get_args();
        foreach ($this->progressCallbacks as $func) {
            call_user_func_array($func, $args);
        }

        return $this;
    }

    public function resolve()
    {
        if (DeferredInterface::STATE_REJECTED === $this->state) {
            throw new \LogicException('Cannot resolve a deferred object that has already been rejected');
        }

        if (DeferredInterface::STATE_RESOLVED === $this->state) {
            return $this;
        }

        $this->state = DeferredInterface::STATE_RESOLVED;
        $this->callbackArgs = func_get_args();

        while ($func = array_shift($this->alwaysCallbacks)) {
            call_user_func_array($func, $this->callbackArgs);
        }

        while ($func = array_shift($this->doneCallbacks)) {
            call_user_func_array($func, $this->callbackArgs);
        }

        return $this;
    }

    public function reject()
    {
        if (DeferredInterface::STATE_RESOLVED === $this->state) {
            throw new \LogicException('Cannot reject a deferred object that has already been resolved');
        }

        if (DeferredInterface::STATE_REJECTED === $this->state) {
            return $this;
        }

        $this->state = DeferredInterface::STATE_REJECTED;
        $this->callbackArgs = func_get_args();

        while ($func = array_shift($this->alwaysCallbacks)) {
            call_user_func_array($func, $this->callbackArgs);
        }

        while ($func = array_shift($this->failCallbacks)) {
            call_user_func_array($func, $this->callbackArgs);
        }

        return $this;
    }
}
