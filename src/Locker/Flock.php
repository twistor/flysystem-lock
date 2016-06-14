<?php

namespace Twistor\Flysystem\Locker;

use League\Flysystem\Util;
use Twistor\Flysystem\Exception\LockUnavaibleException;
use Twistor\Flysystem\Exception\UnlockFailedException;
use Twistor\Flysystem\LockerInterface;

/**
 * A locking inplementation that is cross-platform compatible using flock().
 *
 * The drawbacks are that it uses the filesystem, and doesn't work across
 * web heads.
 *
 * @todo Add support for timeouts using LOCK_NB.
 * @todo Is there a way to clean up the files after ourselves?
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
     * @param string $prefix   The lock prefix.
     * @param string $temp_dir (Optional) The temporary directory.
     */
    public function __construct($prefix, $temp_dir = null)
    {
        $this->tempDir = $temp_dir === null ? sys_get_temp_dir() : rtrim($temp_dir, '\/');

        $this->tempDir .= DIRECTORY_SEPARATOR;
        $this->tempDir .= 'flysystem-lock' . DIRECTORY_SEPARATOR;
        $this->tempDir .= Util::normalizePath($prefix) . DIRECTORY_SEPARATOR;

        if ( ! is_dir($this->tempDir)) {
            @mkdir($this->tempDir, 0777, true);

            if ( ! is_dir($this->tempDir)) {
                throw new \InvalidArgumentException();
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function acquireRead($path)
    {
        return $this->lock($path, \LOCK_SH);
    }

    /**
     * @inheritdoc
     */
    public function acquireWrite($path)
    {
        return $this->lock($path, \LOCK_EX);
    }

    /**
     * @inheritdoc
     */
    public function releaseRead($path, $lock)
    {
        $this->unlock($path, $lock);
    }

    /**
     * @inheritdoc
     */
    public function releaseWrite($path, $lock)
    {
        $this->unlock($path, $lock);
    }

    /**
     * Returns the lock.
     *
     * @param string $path
     * @param int    $operation
     *
     * @return resource
     *
     * @throws LockUnavaibleException
     */
    private function lock($path, $operation)
    {
        $handle = fopen($this->tempDir . sha1($path) . '.lock', 'c');

        if ($handle && flock($handle, $operation)) {
            return $handle;
        }

        throw new LockUnavaibleException($path);
    }

    /**
     * Unlocks a lock.
     *
     * @param string $path
     * @param resource $handle
     *
     * @throws UnlockFailedException
     */
    private function unlock($path, $handle)
    {
        try {
            if ( ! flock($handle, \LOCK_UN)) {
                throw new UnlockFailedException($path);
            }
        } finally {
            is_resource($handle) && fclose($handle);
        }
    }
}
