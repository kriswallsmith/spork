<?php

/*
 * This file is part of Spork, an OpenSky project.
 *
 * (c) 2012 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spork\Batch\Strategy;

use Spork\Exception\UnexpectedTypeException;

/**
 * Processes a Mongo cursor.
 *
 * If you use this strategy you MUST close your Mongo connection on the
 * spork.pre_fork event.
 *
 *     $mongo = new Mongo();
 *     $manager->addListener(Events::PRE_FORK, array($mongo, 'close'));
 */
class MongoStrategy extends AbstractStrategy
{
    private $size;

    public function __construct($size = 3)
    {
        $this->size = $size;
    }

    public function createBatches($cursor)
    {
        if (!$cursor instanceof \MongoCursor) {
            throw new UnexpectedTypeException($cursor, 'MongoCursor');
        }

        $limit = ceil($cursor->count() / $this->size);

        $batches = array();
        for ($i = 0; $i < $this->size; $i++) {
            $batches[] = function() use($cursor, $i, $limit) {
                return $cursor->skip($i * $limit)->limit($limit);
            };
        }

        return $batches;
    }
}
