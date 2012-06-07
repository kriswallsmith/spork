<?php

/*
 * This file is part of Spork, an OpenSky project.
 *
 * (c) 2012 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spork\Util;

/**
 * Throttles iteration based on a system load threshold.
 */
class ThrottleIterator implements \OuterIterator
{
    private $inner;
    private $threshold;
    private $lastThrottle;

    public function __construct($inner, $threshold)
    {
        if (!is_callable($inner) && !is_array($inner) && !$inner instanceof \Traversable) {
            throw new UnexpectedTypeException($inner, 'callable, array, or Traversable');
        }

        $this->inner = $inner;
        $this->threshold = $threshold;
    }

    /**
     * Attempts to lazily resolve the supplied inner to an instance of Iterator.
     */
    public function getInnerIterator()
    {
        if (is_callable($this->inner)) {
            // callable
            $this->inner = call_user_func($this->inner);
        }

        if (is_array($this->inner)) {
            // array
            $this->inner = new \ArrayIterator($this->inner);
        } elseif ($this->inner instanceof \IteratorAggregate) {
            // IteratorAggregate
            while ($this->inner instanceof \IteratorAggregate) {
                $this->inner = $this->inner->getIterator();
            }
        }

        if (!$this->inner instanceof \Iterator) {
            throw new UnexpectedTypeException($this->inner, 'Iterator');
        }

        return $this->inner;
    }

    public function current()
    {
        // only throttle every 5s
        if ($this->lastThrottle < time() - 5) {
            $this->throttle();
        }

        return $this->getInnerIterator()->current();
    }

    public function key()
    {
        return $this->getInnerIterator()->key();
    }

    public function next()
    {
        return $this->getInnerIterator()->next();
    }

    public function rewind()
    {
        return $this->getInnerIterator()->rewind();
    }

    public function valid()
    {
        return $this->getInnerIterator()->valid();
    }

    protected function getLoad()
    {
        list($load) = sys_getloadavg();

        return $load;
    }

    protected function sleep($period)
    {
        sleep($period);
    }

    private function throttle($period = 1)
    {
        $this->lastThrottle = time();

        if ($this->threshold <= $this->getLoad()) {
            $this->sleep($period);
            $this->throttle($period * 2);
        }
    }
}
