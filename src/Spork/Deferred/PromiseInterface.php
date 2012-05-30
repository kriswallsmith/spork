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

interface PromiseInterface
{
    const STATE_PENDING  = 'pending';
    const STATE_RESOLVED = 'resolved';
    const STATE_REJECTED = 'rejected';

    public function getState();
    public function always($alwaysCallback);
    public function done($doneCallback);
    public function fail($failCallback);
    public function then($doneCallback, $failCallback = null);
}
