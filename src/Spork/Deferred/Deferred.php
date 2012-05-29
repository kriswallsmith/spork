<?php

/*
 * This file is part of Spork, an OpenSky project.
 *
 * (c) 2012 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spork\Deferred;

use Spork\Exception\UnexpectedTypeException;

class Deferred implements DeferredInterface
{
    private $state;
    private $alwaysCallbacks;
    private $doneCallbacks;
    private $failCallbacks;
    private $callbackArgs;

    public function __construct()
    {
        $this->state = DeferredInterface::STATE_PENDING;

        $this->alwaysCallbacks = array();
        $this->doneCallbacks = array();
        $this->failCallbacks = array();
    }

    public function getState()
    {
        return $this->state;
    }

    public function always($alwaysCallback)
    {
        if (!is_callable($alwaysCallback)) {
            throw new UnexpectedTypeException($alwaysCallback, 'callable');
        }

        switch ($this->state) {
            case DeferredInterface::STATE_PENDING:
                $this->alwaysCallbacks[] = $alwaysCallback;
                break;
            default:
                call_user_func_array($alwaysCallback, $this->callbackArgs);
                break;
        }

        return $this;
    }

    public function done($doneCallback)
    {
        if (!is_callable($doneCallback)) {
            throw new UnexpectedTypeException($doneCallback, 'callable');
        }

        switch ($this->state) {
            case DeferredInterface::STATE_PENDING:
                $this->doneCallbacks[] = $doneCallback;
                break;
            case DeferredInterface::STATE_RESOLVED:
                call_user_func_array($doneCallback, $this->callbackArgs);
        }

        return $this;
    }

    public function fail($failCallback)
    {
        if (!is_callable($failCallback)) {
            throw new UnexpectedTypeException($failCallback, 'callable');
        }

        switch ($this->state) {
            case DeferredInterface::STATE_PENDING:
                $this->failCallbacks[] = $failCallback;
                break;
            case DeferredInterface::STATE_REJECTED:
                call_user_func_array($failCallback, $this->callbackArgs);
                break;
        }

        return $this;
    }

    public function then($doneCallback, $failCallback = null)
    {
        $this->done($doneCallback);

        if ($failCallback) {
            $this->fail($failCallback);
        }

        return $this;
    }

    public function resolve()
    {
        if (DeferredInterface::STATE_REJECTED === $this->state) {
            throw new \LogicException('Cannot resolve a deferred object that has already been rejected');
        }

        if (DeferredInterface::STATE_RESOLVED === $this->state) {
            return;
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
            return;
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
