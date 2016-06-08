<?php

namespace Twistor\Flysystem;

class Lock
{
    public function __construct($path)
    {
        $this->path = $path;
    }

    public function getPath()
    {
        return $this->path;
    }
}
