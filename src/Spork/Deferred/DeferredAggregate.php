<?php

/*
 * This file is part of Spork, an OpenSky project.
 *
 * (c) OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spork\Deferred;

use Spork\Exception\UnexpectedTypeException;

class DeferredAggregate implements PromiseInterface
{
    private $children;
    private $delegate;

    public function __construct(array $children)
    {
        // validate children
        foreach ($children as $child) {
            if (!$child instanceof PromiseInterface) {
                throw new UnexpectedTypeException($child, 'Spork\Deferred\PromiseInterface');
            }
        }

        $this->children = $children;
        $this->delegate = new Deferred();

        // connect to each child
        foreach ($this->children as $child) {
            $child->always(array($this, 'tick'));
        }

        // always tick once now
        $this->tick();
    }

    public function getState()
    {
        return $this->delegate->getState();
    }

    public function getChildren()
    {
        return $this->children;
    }

    public function always($always)
    {
        $this->delegate->always($always);

        return $this;
    }

    public function done($done)
    {
        $this->delegate->done($done);

        return $this;
    }

    public function fail($fail)
    {
        $this->delegate->fail($fail);

        return $this;
    }

    public function then($done, $fail = null)
    {
        $this->delegate->then($done, $fail);

        return $this;
    }

    public function tick()
    {
        $pending = count($this->children);

        foreach ($this->children as $child) {
            switch ($child->getState()) {
                case PromiseInterface::STATE_REJECTED:
                    $this->delegate->reject($this);

                    return;
                case PromiseInterface::STATE_RESOLVED:
                    --$pending;
                    break;
            }
        }

        if (!$pending) {
            $this->delegate->resolve($this);
        }
    }
}
