<?php

/*
 * This file is part of Spork, an OpenSky project.
 *
 * (c) OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spork\EventDispatcher;

use Symfony\Component\EventDispatcher\EventDispatcherInterface as BaseEventDispatcherInterface;

interface EventDispatcherInterface extends BaseEventDispatcherInterface
{
    public function dispatchSignal($signal);
    public function addSignalListener($signal, $callable, $priority = 0);
    public function removeSignalListener($signal, $callable);
}
