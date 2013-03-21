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
 * Creates the batch iterator using array_chunk().
 */
class ChunkStrategy extends AbstractStrategy
{
    private $forks;
    private $preserveKeys;

    public function __construct($forks = 3, $preserveKeys = false)
    {
        $this->forks = $forks;
        $this->preserveKeys = $preserveKeys;
    }

    public function createBatches($data)
    {
        if (!is_array($data) && !$data instanceof \Traversable) {
            throw new UnexpectedTypeException($data, 'array or Traversable');
        }

        if ($data instanceof \Traversable) {
            $data = iterator_to_array($data);
        }

        $size = ceil(count($data) / $this->forks);

        return array_chunk($data, $size, $this->preserveKeys);
    }
}
