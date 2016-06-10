<?php

namespace Twistor\Flysystem\Locker;

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
 */
class Flock implements LockerInterface
{
    /**
     * The lock prefix.
     *
     * @var string
     */
    private $prefix;

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
        $this->prefix = $prefix;
        $this->tempDir = $temp_dir === null ? sys_get_temp_dir() : rtrim($temp_dir, '\/');
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
    public function releaseRead($lock)
    {
        $this->unlock($lock);
    }

    /**
     * @inheritdoc
     */
    public function releaseWrite($lock)
    {
        $this->unlock($lock);
    }

    /**
     * Performs the flock() call.
     *
     * @param string $key
     * @param int    $operation
     *
     * @return bool
     */
    private function flock($key, $operation)
    {
        try {
            $handle = fopen($key, 'c');

            if ($handle && flock($handle, $operation)) {
                return true;
            }

            return false;
        } finally {
            is_resource($handle) && fclose($handle);
        }
    }

    /**
     * Returns the lock.
     *
     * @param string $path
     * @param int    $operation
     *
     * @return array
     *
     * @throws LockUnavaibleException
     */
    private function lock($path, $operation)
    {
        $key = $this->getLockKey($path);

        if ( ! $this->flock($key, $operation)) {
            throw new LockUnavaibleException($path);
        }

        return compact('key', 'path');
    }

    /**
     * Unlocks a lock.
     *
     * @param array $lock
     *
     * @throws UnlockFailedException
     */
    private function unlock(array $lock)
    {
        if ( ! $this->flock($lock['key'], \LOCK_UN)) {
            throw new UnlockFailedException($lock['path']);
        }
    }

    /**
     * Returns the lock path.
     *
     * @param string $path
     *
     * @return string
     */
    private function getLockKey($path)
    {
        $path = $this->prefix . '://' . $path;

        return $this->tempDir . '/flysystem-lock-' . sha1($path) . '.lock';
    }
}
