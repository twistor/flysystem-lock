<?php

namespace Twistor\Flysystem\Lock;

use Twistor\Flysystem\Exception\LockUnavaibleException;
use Twistor\Flysystem\LockerInterface;

/**
 * A locking inplementation that is cross-platform compatible using flock().
 *
 * The drawbacks are that it uses the filesystem, and doesn't work across
 * web heads.
 *
 * @todo Add support for timeouts using LOCK_NB.
 */
class Flock implements LockerInterface
{
    /**
     * The temporary directory.
     *
     * @var string
     */
    private $tempDir;

    /**
     * Constructs a Flock object.
     *
     * @param string $temp_dir (Optional) The temporary directory.
     */
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
    public function releaseRead($lock)
    {
        $this->flock($lock, \LOCK_UN);
    }

    /**
     * @inheritdoc
     */
    public function releaseWrite($lock)
    {
        $this->flock($lock, \LOCK_UN);
    }

    /**
     * Returns the lock path.
     *
     * @param string
     *
     * @return string
     */
    private function getLockPath($path)
    {
        return $this->tempDir . '/flysystem-lock-' . sha1($path) . '.lock';
    }

    /**
     * Performs the flock() call.
     *
     * @param string $path
     * @param int    $operation
     *
     * @throws LockUnavaibleException
     */
    private function flock($path, $operation)
    {
        try {
            $handle = fopen($path, 'c');

            if ($handle && flock($handle, $operation)) {
                return $path;
            }

            throw new LockUnavaibleException($path);
        } finally {
            is_resource($handle) && fclose($handle);
        }
    }
}
