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

class DoctrineMongoStrategy extends MongoStrategy
{
    const DATA_CLASS = 'Doctrine\MongoDB\Cursor';
}
