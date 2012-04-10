<?php

/*
 * This file is part of the Spork package, an OpenSky project.
 *
 * (c) 2010-2011 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spork\Deferred;

interface PromiseInterface
{
    const STATE_PENDING  = 'pending';
    const STATE_RESOLVED = 'resolved';
    const STATE_REJECTED = 'rejected';

    function getState();
    function always($alwaysCallback);
    function done($doneCallback);
    function fail($failCallback);
    function then($doneCallback, $failCallback = null);
}
