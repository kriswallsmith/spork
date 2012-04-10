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

class DeferredFactory implements FactoryInterface
{
    public function createDeferred()
    {
        return new Deferred();
    }
}
