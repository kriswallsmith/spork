<?php

/*
 * This file is part of Spork, an OpenSky project.
 *
 * (c) OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spork\Test\Util;

use Spork\Util\ThrottleIterator;

class ThrottleIteratorStub extends ThrottleIterator
{
    public $loads = array();
    public $sleeps = array();

    protected function getLoad()
    {
        return (integer) array_shift($this->loads);
    }

    protected function sleep($period)
    {
        $this->sleeps[] = $period;
    }
}
