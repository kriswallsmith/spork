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

final class Events
{
    /**
     * Dispatched in the parent process before forking.
     */
    const PRE_FORK = 'spork.pre_fork';

    /**
     * Notifies in the child process after forking.
     */
    const POST_FORK = 'spork.post_fork';
}
