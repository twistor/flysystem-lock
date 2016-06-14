<?php

namespace Twistor\Flysystem\Locker;

use Twistor\Flysystem\Exception\LockUnavaibleException;
use Twistor\Flysystem\Exception\UnlockFailedException;
use Twistor\Flysystem\LockerInterface;

/**
 * A locking inplementation using APCu.
 */
class Apc implements LockerInterface
{
    private $prefix;

    /**
     * Constructs a Flock object.
     *
     * @param string $prefix   The lock prefix.
     */
    public function __construct($prefix)
    {
        $this->prefix = 'flysystem-lock:' . $prefix;
    }

    /**
     * @inheritdoc
     */
    public function acquireRead($path)
    {
        $this->getWriteMutex($path);

        $read = $this->prefix . ':r:' . $path;

        // Increment readers.
        apc_add($read, 0);
        apc_inc($read);

        $this->releaseWriteMutex($path);

        return $read;
    }

    /**
     * @inheritdoc
     */
    public function acquireWrite($path)
    {
        $this->getWriteMutex($path);

        $read = $this->prefix . ':r:' . $path;

        // Wait for readers to stop.
        while (apc_fetch($read)) {
            usleep(0);
        }

        return $path;
    }

    /**
     * @inheritdoc
     */
    public function releaseRead($path, $lock)
    {
        // Decrement readers.
        if (apc_dec($lock) === false) {
            throw new \Exception(":r: does not exist");
        }
    }

    /**
     * @inheritdoc
     */
    public function releaseWrite($path, $lock)
    {
        $this->releaseWriteMutex($path);
    }

    private function getWriteMutex($path)
    {
        $write = $this->prefix . ':w:' . $path;

        while ( ! apc_add($write, 1)) {
            usleep(0);
        }
    }

    private function releaseWriteMutex($path)
    {
        if ( ! apc_delete($this->prefix . ':w:' . $path)) {
            throw new \Exception(':w: does not exist');
        }
    }
}
