<?php

namespace Twistor\Flysystem\Lock;

use Twistor\Flysystem\Exception\LockUnavaibleException;
use Twistor\Flysystem\Lock;
use Twistor\Flysystem\LockerInterface;

class Flock implements LockerInterface
{
    private $tempDir;

    public function __construct($temp_dir = null)
    {
        $this->tempDir = $temp_dir === null ? sys_get_temp_dir() : rtrim($temp_dir, '\/');
    }

    /**
     * @inheritdoc
     */
    public function acquireRead($path)
    {
        return $this->flock($this->getLockPath($path), \LOCK_SH);
    }

    /**
     * @inheritdoc
     */
    public function acquireWrite($path)
    {
        return $this->flock($this->getLockPath($path), \LOCK_EX);
    }

    /**
     * @inheritdoc
     */
    public function release(Lock $lock)
    {
        $this->flock($lock->getPath(), \LOCK_UN);
    }

    private function getLockPath($path)
    {
        return $this->tempDir . '/flysystem-lock-' . sha1($path) . '.lock';
    }

    private function flock($path, $operation)
    {
        try {
            $handle = fopen($path, 'c');

            if ($handle && flock($handle, $operation)) {
                return new Lock($path);
            }

            throw new LockUnavaibleException($path);
        } finally {
            is_resource($handle) && fclose($handle);
        }
    }
}
