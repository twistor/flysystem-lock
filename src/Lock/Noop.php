<?php

namespace Twistor\Flysystem\Lock;

use Twistor\Flysystem\LockerInterface;

class Noop implements LockerInterface
{
    public function acquireRead($path)
    {
    }

    public function acquireWrite($path)
    {
    }

    public function release($lock)
    {
    }
}
