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
     * @return Lock
     */
    public function acquireRead($path);

    /**
     * Acquires a write lock.
     *
     * @param string $path
     *
     * @return Lock
     */
    public function acquireWrite($path);

    /**
     * Releases the read lock.
     *
     * @param $lock The previoulsy acquired lock.
     */
    public function releaseRead($lock);

    /**
     * Releases the write lock.
     *
     * @param $lock The previoulsy acquired lock.
     */
    public function releaseWrite($lock);
}
