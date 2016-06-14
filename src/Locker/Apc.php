<?php

namespace Twistor\Flysystem\Locker;

use Twistor\Flysystem\Exception\LockUnavaibleException;
use Twistor\Flysystem\Exception\UnlockFailedException;
use Twistor\Flysystem\LockerInterface;

/**
 * A locking inplementation using APCu.
 *
 * @todo Implement timeouts.
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
        $write = $this->getWriteMutex($path);

        $read = $this->prefix . ':r:' . $path;

        // Increment readers.
        apc_add($read, 0);
        apc_inc($read);

        $this->releaseWrite($path, $write);

        return $read;
    }

    /**
     * @inheritdoc
     */
    public function acquireWrite($path)
    {
        $write = $this->getWriteMutex($path);

        $read = $this->prefix . ':r:' . $path;

        // Wait for readers to stop.
        while (apc_fetch($read)) {
            usleep(0);
        }

        return $write;
    }

    /**
     * @inheritdoc
     */
    public function releaseRead($path, $lock)
    {
        // Decrement readers.
        if (apc_dec($lock) === false) {
            throw new UnlockFailedException($path);
        }
    }

    /**
     * @inheritdoc
     */
    public function releaseWrite($path, $lock)
    {
        if ( ! apc_delete($lock)) {
            throw new UnlockFailedException($path);
        }
    }

    /**
     * Gets the write mutex.
     *
     * @param string $path
     *
     * @return string
     */
    private function getWriteMutex($path)
    {
        $write = $this->prefix . ':w:' . $path;

        while ( ! apc_add($write, 1)) {
            usleep(0);
        }

        return $write;
    }
}
