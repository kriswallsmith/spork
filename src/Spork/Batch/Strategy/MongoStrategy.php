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
    const DATA_CLASS = 'MongoCursor';

    private $size;
    private $skip;

    /**
     * Constructor.
     *
     * @param integer $size The number of batches to create
     * @param integer $skip The number of documents to skip
     */
    public function __construct($size = 3, $skip = 0)
    {
        $this->size = $size;
        $this->skip = $skip;
    }

    public function createBatches($cursor)
    {
        $expected = static::DATA_CLASS;
        if (!$cursor instanceof $expected) {
            throw new UnexpectedTypeException($cursor, $expected);
        }

        $skip  = $this->skip;
        $limit = ceil(($cursor->count() - $skip) / $this->size);

        $batches = array();
        for ($i = 0; $i < $this->size; $i++) {
            $batches[] = function() use($cursor, $skip, $i, $limit) {
                return $cursor->skip($skip + $i * $limit)->limit($limit);
            };
        }

        return $batches;
    }
}
