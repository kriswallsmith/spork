<?php

namespace Spork\Deferred;

use Spork\Exception\UnexpectedTypeException;

class DeferredAggregate implements PromiseInterface
{
    private $children;
    private $delegate;

    public function __construct(array $children)
    {
        // connect to each deferred
        foreach ($children as $child) {
            if (!$child instanceof PromiseInterface) {
                throw new UnexpectedTypeException($child, 'Spork\PromiseInterface');
            }

            $child->always(array($this, 'tick'));
        }

        $this->children = $children;
        $this->delegate = new Deferred();
    }

    public function getState()
    {
        return $this->delegate->getState();
    }

    public function always($alwaysCallback)
    {
        $this->delegate->always($alwaysCallback);

        return $this;
    }

    public function done($doneCallback)
    {
        $this->delegate->done($doneCallback);

        return $this;
    }

    public function fail($failCallback)
    {
        $this->delegate->fail($failCallback);

        return $this;
    }

    public function then($doneCallback, $failCallback = null)
    {
        $this->delegate->then($doneCallback, $failCallback);

        return $this;
    }

    public function tick()
    {
        $pending = count($this->children);

        foreach ($this->children as $child) {
            switch ($child->getState()) {
                case PromiseInterface::STATE_REJECTED:
                    $this->delegate->reject();
                    return;
                case PromiseInterface::STATE_RESOLVED:
                    --$pending;
                    break;
            }
        }

        if (!$pending) {
            $this->delegate->resolve();
        }
    }
}
