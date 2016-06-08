<?php

namespace Twistor\Flysystem\Tests;

use League\Flysystem\Filesystem;
use Prophecy\Argument;
use Twistor\Flysystem\Lock\Noop;
use Twistor\Flysystem\LockingFilesystem;

require_once dirname(__DIR__) . '/vendor/league/flysystem/tests/FilesystemTests.php';

class LockingFilesystemTest extends \FilesystemTests
{
    /**
     * @before
     */
    public function setupAdapter()
    {
        $this->prophecy = $this->prophesize('League\Flysystem\AdapterInterface');
        $this->adapter = $this->prophecy->reveal();
        $this->filesystem = new LockingFilesystem(new Filesystem($this->adapter), new Noop());
        $this->config = Argument::type('League\Flysystem\Config');
    }
}
