<?php

namespace Twistor\Flysystem;

use Twistor\Flysystem\Lock;

interface LockerInterface
{
    /**
     * Acquires a read lock.
     *
     * @param string $path
     *
     * @return mixed
     */
    public function acquireRead($path);

    /**
     * Acquires a write lock.
     *
     * @param string $path
     *
     * @return mixed
     */
    public function acquireWrite($path);

    /**
     * Releases the read lock.
     *
     * @param string $path The path to unlock.
     * @param mixed  $lock The previoulsy acquired lock.
     */
    public function releaseRead($path, $lock);

    /**
     * Releases the write lock.
     *
     * @param string $path The path to unlock.
     * @param mixed  $lock The previoulsy acquired lock.
     */
    public function releaseWrite($path, $lock);
}
