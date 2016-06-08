<?php

namespace Twistor\Flysystem\Tests;

use League\Flysystem\Filesystem;
use Prophecy\Argument;
use Twistor\Flysystem\Lock\Flock;
use Twistor\Flysystem\LockingFilesystem;

class LockingFilesystemFlockTest extends LockingFilesystemTest
{
    /**
     * @before
     */
    public function setupAdapter()
    {
        $this->prophecy = $this->prophesize('League\Flysystem\AdapterInterface');
        $this->adapter = $this->prophecy->reveal();
        $this->filesystem = new LockingFilesystem(new Filesystem($this->adapter), new Flock());
        $this->config = Argument::type('League\Flysystem\Config');
    }
}
