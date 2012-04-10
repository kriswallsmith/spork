<?php

namespace Spork\Deferred;

interface DeferredInterface extends PromiseInterface
{
    function resolve();
    function reject();
}
