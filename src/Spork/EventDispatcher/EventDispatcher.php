<?php

/*
 * This file is part of Spork, an OpenSky project.
 *
 * (c) OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(ticks=1);

namespace Spork\EventDispatcher;

use Symfony\Component\EventDispatcher\EventDispatcher as BaseEventDispatcher;

/**
 * Adds support for listening to signals.
 */
class EventDispatcher extends BaseEventDispatcher implements EventDispatcherInterface
{
    public function dispatchSignal($signal)
    {
        $this->dispatch('spork.signal.'.$signal);
    }

    public function addSignalListener($signal, $callable, $priority = 0)
    {
        $this->addListener('spork.signal.'.$signal, $callable, $priority);
        pcntl_signal($signal, array($this, 'dispatchSignal'));
    }

    public function removeSignalListener($signal, $callable)
    {
        $this->removeListener('spork.signal.'.$signal, $callable);
    }
}
