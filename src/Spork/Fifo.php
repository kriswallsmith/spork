<?php

/*
 * This file is part of Spork, an OpenSky project.
 *
 * (c) OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spork;

use Spork\Exception\ProcessControlException;

class Fifo
{
    private $pid;
    private $read;
    private $write;

    public function __construct($pid = null)
    {
        $directions = array('up', 'down');

        if (null === $pid) {
            // child
            $pid   = posix_getpid();
            $modes = array('write', 'read');
        } else {
            // parent
            $modes = array('read', 'write');
        }

        $this->pid = $pid;

        foreach (array_combine($directions, $modes) as $direction => $mode) {
            $fifo = realpath(sys_get_temp_dir()).'/spork'.$this->pid.'.'.$direction;

            if (!file_exists($fifo) && !posix_mkfifo($fifo, 0600) && 17 !== $error = posix_get_last_error()) {
                throw new ProcessControlException(sprintf('Error while creating FIFO: %s (%d)', posix_strerror($error), $error));
            }

            $this->$mode = fopen($fifo, $mode[0]);
        }
    }

    public function __destruct()
    {
        $this->close();
    }

    public function receive()
    {
        if (false === $data = stream_get_contents($this->read)) {
            throw new ProcessControlException('Unable to read from FIFO');
        }

        return unserialize($data);
    }

    /**
     * Writes data to the FIFO and optionally signals the process.
     *
     * @param mixed $data   The data to send
     * @param mixed $signal A signal to send upon writing
     */
    public function send($data, $signal = null)
    {
        if (false === fwrite($this->write, serialize($data))) {
            throw new ProcessControlException('Unable to write to FIFO');
        }

        if (null !== $signal) {
            $this->signal($signal);
        }
    }

    /**
     * Sends a signal to the process.
     */
    public function signal($signal)
    {
        return posix_kill($this->pid, $signal);
    }

    public function close()
    {
        if (is_resource($this->read)) {
            fclose($this->read);
        }

        if (is_resource($this->write)) {
            fclose($this->write);
        }
    }
}
