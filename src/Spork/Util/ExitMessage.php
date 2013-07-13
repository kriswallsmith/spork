<?php

/*
 * This file is part of Spork, an OpenSky project.
 *
 * (c) OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spork\Util;

class ExitMessage implements \Serializable
{
    private $result;
    private $output;
    private $error;

    public function getResult()
    {
        return $this->result;
    }

    public function setResult($result)
    {
        $this->result = $result;
    }

    public function getOutput()
    {
        return $this->output;
    }

    public function setOutput($output)
    {
        $this->output = $output;
    }

    public function getError()
    {
        return $this->error;
    }

    public function setError(Error $error)
    {
        $this->error = $error;
    }

    public function serialize()
    {
        return serialize(array(
            $this->result,
            $this->output,
            $this->error,
        ));
    }

    public function unserialize($str)
    {
        list(
            $this->result,
            $this->output,
            $this->error
        ) = unserialize($str);
    }
}
