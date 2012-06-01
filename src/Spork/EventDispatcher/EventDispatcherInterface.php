<?php

/*
 * This file is part of Spork, an OpenSky project.
 *
 * (c) 2012 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spork\EventDispatcher;

use Symfony\Component\EventDispatcher\EventDispatcherInterface as BaseEventDispatcherInterface;

interface EventDispatcherInterface extends BaseEventDispatcherInterface
{
    function dispatchSignal($signal);
    function addSignalListener($signal, $callable, $priority = 0);
    function removeSignalListener($signal, $callable);
}
