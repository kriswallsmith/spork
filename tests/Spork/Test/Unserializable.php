<?php

/*
 * This file is part of Spork, an OpenSky project.
 *
 * (c) OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spork\Test;

class Unserializable
{
    public function __sleep()
    {
        throw new \Exception('Hey, don\'t serialize me!');
    }
}
