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

interface DeferredInterface extends PromiseInterface
{
    /**
     * Marks the current promise as successful.
     *
     * Calls "always" callbacks first, followed by "done" callbacks.
     *
     * @param mixed $args Any arguments will be passed along to the callbacks
     *
     * @return DeferredInterface The current promise
     * @throws \LogicException   If the promise was previously rejected
     */
    function resolve();

    /**
     * Marks the current promise as failed.
     *
     * Calls "always" callbacks first, followed by "fail" callbacks.
     *
     * @param mixed $args Any arguments will be passed along to the callbacks
     *
     * @return DeferredInterface The current promise
     * @throws \LogicException   If the promise was previously resolved
     */
    function reject();
}
