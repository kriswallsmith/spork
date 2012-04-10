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

interface DeferredInterface extends PromiseInterface
{
    function resolve();
    function reject();
}
