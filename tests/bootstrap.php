<?php

/*
 * This file is part of the Spork package, an OpenSky project.
 *
 * (c) 2012 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

spl_autoload_register(function($class)
{
    if (0 === strpos($class, 'Spork\\Test\\') && file_exists($file = __DIR__.'/'.str_replace('\\', '/', $class).'.php')) {
        require_once $file;
    } elseif (0 === strpos($class, 'Spork\\') && file_exists($file = __DIR__.'/../src/'.str_replace('\\', '/', $class).'.php')) {
        require_once $file;
    }
});
