<?php

namespace Spork\Deferred;

class DeferredFactory implements FactoryInterface
{
    public function createDeferred()
    {
        return new Deferred();
    }
}
