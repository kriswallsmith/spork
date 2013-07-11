<?php

/*
 * This file is part of Spork, an OpenSky project.
 *
 * (c) OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spork\Exception;

/**
 * Turns an error passed through a FIFO into an exception.
 */
class ForkException extends \RuntimeException
{
    private $name;
    private $pid;
    private $error;

    public function __construct($name, $pid, array $error = null)
    {
        $this->name = $name;
        $this->pid = $pid;
        $this->error = $error;

        if ($this->error) {
            list($class, $message, $file, $line, $code) = $this->error;

            if (__CLASS__ === $class) {
                parent::__construct(sprintf(
                    '%s via "%s" fork (%d)',
                    $message, $this->name, $this->pid
                ));
            } else {
                parent::__construct(sprintf(
                    '%s (%d) thrown in "%s" fork (%d): "%s" (%s:%d)',
                    $class, $code, $this->name, $this->pid, $message, $file, $line
                ));
            }
        } else {
            parent::__construct(sprintf(
                'An unknown error occurred in "%s" fork (%d)',
                $this->name, $this->pid
            ));
        }
    }

    public function getPid()
    {
        return $this->pid;
    }

    public function getError()
    {
        return $this->error;
    }
}
