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

interface PromiseInterface
{
    const STATE_PENDING  = 'pending';
    const STATE_RESOLVED = 'resolved';
    const STATE_REJECTED = 'rejected';

    /**
     * Returns the promise state.
     *
     *  * PromiseInterface::STATE_PENDING:  The promise is still open
     *  * PromiseInterface::STATE_RESOLVED: The promise completed successfully
     *  * PromiseInterface::STATE_REJECTED: The promise failed
     *
     * @return string A promise state constant
     */
    function getState();

    /**
     * Adds a callback to be called whether the promise is resolved or rejected.
     *
     * The callback will be called immediately if the promise is no longer
     * pending.
     *
     * @param callable $always The callback
     *
     * @return PromiseInterface The current promise
     */
    function always($always);

    /**
     * Adds a callback to be called when the promise completes successfully.
     *
     * The callback will be called immediately if the promise state is resolved.
     *
     * @param callable $done The callback
     *
     * @return PromiseInterface The current promise
     */
    function done($done);

    /**
     * Adds a callback to be called when the promise fails.
     *
     * The callback will be called immediately if the promise state is rejected.
     *
     * @param callable $done The callback
     *
     * @return PromiseInterface The current promise
     */
    function fail($fail);

    /**
     * Adds done and fail callbacks.
     *
     * @param callable $done The done callback
     * @param callable $fail The fail callback
     *
     * @return PromiseInterface The current promise
     */
    function then($done, $fail = null);
}
