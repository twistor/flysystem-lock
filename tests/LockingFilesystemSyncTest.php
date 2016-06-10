<?php

namespace Twistor\Flysystem\Tests;

use League\Flysystem\Filesystem;
use Prophecy\Argument;
use Twistor\Flysystem\Lock\Sync;
use Twistor\Flysystem\LockingFilesystem;

class LockingFilesystemSyncTest extends LockingFilesystemTest
{
    /**
     * @before
     */
    public function setupAdapter()
    {
        $this->prophecy = $this->prophesize('League\Flysystem\AdapterInterface');
        $this->adapter = $this->prophecy->reveal();

        $filesystem = new Filesystem($this->adapter);
        $locker = new Sync('prefix');

        $this->filesystem = new LockingFilesystem($filesystem, $locker);
        $this->config = Argument::type('League\Flysystem\Config');
    }
}
