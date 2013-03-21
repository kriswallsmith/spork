<?php

/*
 * This file is part of Spork, an OpenSky project.
 *
 * (c) OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spork\Batch\Strategy;

use Spork\Batch\BatchRunner;

abstract class AbstractStrategy implements StrategyInterface
{
    public function createRunner($batch, $callback)
    {
        return new BatchRunner($batch, $callback);
    }
}
