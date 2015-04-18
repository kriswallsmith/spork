<?php

/*
 * This file is part of Spork, an OpenSky project.
 *
 * (c) OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spork\Batch;

use Spork\Exception\UnexpectedTypeException;
use Spork\SharedMem;

class BatchRunner
{
    private $batch;
    private $callback;

    /**
     * Constructor.
     *
     * The callback should be a callable with the following signature:
     *
     *     function($item, $index, $batch, $sharedMem)
     *
     * @param mixed    $batch    The batch
     * @param callable $callback The callback
     */
    public function __construct($batch, $callback)
    {
        if (!is_callable($callback)) {
            throw new UnexpectedTypeException($callback, 'callable');
        }

        $this->batch = $batch;
        $this->callback = $callback;
    }

    public function __invoke(SharedMem $sharedMem)
    {
        // lazy batch...
        if ($this->batch instanceof \Closure) {
            $this->batch = call_user_func($this->batch);
        }

        $results = array();
        foreach ($this->batch as $index => $item) {
            $results[$index] = call_user_func($this->callback, $item, $index, $this->batch, $sharedMem);
        }

        return $results;
    }
}
