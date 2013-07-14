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

/**
 * Sends messages between processes.
 */
class Fifo
{
    private $pid;
    private $ppid;
    private $read;
    private $write;
    private $signal;

    /**
     * Constructor.
     *
     * @param integer $pid    The child process id or null if this is the child
     * @param integer $signal The signal to send after writing to the FIFO
     */
    public function __construct($pid = null, $signal = null)
    {
        $directions = array('up', 'down');

        if (null === $pid) {
            // child
            $pid   = posix_getpid();
            $ppid  = posix_getppid();
            $modes = array('write', 'read');
        } else {
            // parent
            $ppid  = null;
            $modes = array('read', 'write');
        }

        $this->pid  = $pid;
        $this->ppid = $ppid;

        foreach (array_combine($directions, $modes) as $direction => $mode) {
            $fifo = $this->getPath($direction);

            if (!file_exists($fifo) && !posix_mkfifo($fifo, 0600) && 17 !== $error = posix_get_last_error()) {
                throw new ProcessControlException(sprintf('Error while creating FIFO: %s (%d)', posix_strerror($error), $error));
            }

            if (false === $this->$mode = fopen($fifo, $mode[0])) {
                throw new \RuntimeException(sprintf('Unable to open %s FIFO.', $mode));
            }
        }

        $this->signal = $signal;
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * Reads one message from the FIFO.
     *
     * @return mixed The message, or null
     */
    public function receiveOne(& $success)
    {
        $success = true;

        $serialized = '';
        while (false !== $data = fgets($this->read)) {
            $serialized .= $data;

            if ('b:0;' === $serialized) {
                return false;
            }

            if (false !== $message = @unserialize($serialized)) {
                return $message;
            }
        }

        $success = false;
    }

    /**
     * Reads all messages from the FIFO.
     *
     * @return array An array of messages
     */
    public function receiveMany()
    {
        $messages = array();

        do {
            $messages[] = $this->receiveOne($success);
        } while($success);

        array_pop($messages);

        return $messages;
    }

    /**
     * Writes a message to the FIFO.
     *
     * @param mixed   $message The message to send
     * @param integer $signal  The signal to send afterward
     * @param integer $pause   The number of microseconds to pause after signalling
     */
    public function send($message, $signal = null, $pause = 500)
    {
        if (false === fwrite($this->write, serialize($message)."\n")) {
            throw new ProcessControlException('Unable to write to FIFO');
        }

        if (false === $signal) {
            return;
        }

        $this->signal($signal ?: $this->signal);
        usleep($pause);
    }

    /**
     * Sends a signal to the other process.
     */
    public function signal($signal)
    {
        $pid = null === $this->ppid ? $this->pid : $this->ppid;

        return posix_kill($pid, $signal);
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

    public function cleanup()
    {
        foreach (array('up', 'down') as $direction) {
            if (file_exists($path = $this->getPath($direction))) {
                unlink($path);
            }
        }
    }

    // private

    private function getPath($direction)
    {
        return realpath(sys_get_temp_dir()).'/spork'.$this->pid.'.'.$direction;
    }
}
