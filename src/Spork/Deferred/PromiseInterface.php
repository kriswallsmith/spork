<?php

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
