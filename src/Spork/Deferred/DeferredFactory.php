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

class DeferredFactory implements FactoryInterface
{
    public function createDeferred()
    {
        return new Deferred();
    }

    public function createDeferredAggregate(array $children)
    {
        return new DeferredAggregate($children);
    }
}
