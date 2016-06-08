<?php

namespace Twistor\Flysystem;

interface LockerInterface {

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
     * Releases the lock.
     *
     * @param $lock The previoulsy acquired lock.
     */
    public function release($lock);
}
