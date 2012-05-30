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

final class Events
{
    /**
     * Notifies the application it is in a new process.
     */
    const ON_FORK = 'spork.fork';
}
