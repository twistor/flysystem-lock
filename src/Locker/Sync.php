<?php

namespace Twistor\Flysystem\Locker;

use Twistor\Flysystem\Exception\LockUnavaibleException;
use Twistor\Flysystem\Exception\UnlockFailedException;
use Twistor\Flysystem\LockerInterface;

/**
 * A locking inplementation that is cross-platform compatible using PECL sync.
 */
class Sync implements LockerInterface
{
    /**
     * A cache of previously created SyncReaderWriter objects.
     *
     * @var \SyncReaderWriter[]
     */
    private $syncs = [];

    /**
     * The lock prefix.
     *
     * @var string
     */
    private $prefix;

    /**
     * The amount of time to wait for locks.
     *
     * @var int
     */
    private $wait;

    /**
     * Constructs a Sync object.
     *
     * @param string $prefix The lock prefix.
     * @param int    $wait   (Optional) The amount of time to wait for locks.
     */
    public function __construct($prefix, $wait = -1)
    {
        $this->prefix = $prefix;
        $this->wait = (int) $wait;
    }

    /**
     * @inheritdoc
     */
    public function acquireRead($path)
    {
        $sync = $this->getSync($path);

        if ($sync->readlock($this->wait)) {
            return $sync;
        }

        throw new LockUnavaibleException($path);
    }

    /**
     * @inheritdoc
     */
    public function acquireWrite($path)
    {
        $sync = $this->getSync($path);

        if ($sync->writelock($this->wait)) {
            return $sync;
        }

        throw new LockUnavaibleException($path);
    }

    /**
     * @inheritdoc
     */
    public function releaseRead($path, $lock)
    {
        if ( ! $lock->readunlock()) {
            throw new UnlockFailedException($path);
        }
    }

    /**
     * @inheritdoc
     */
    public function releaseWrite($path, $lock)
    {
        if ( ! $lock->writeunlock()) {
            throw new UnlockFailedException($path);
        }
    }

    /**
     * Returns a SyncReaderWriter.
     *
     * @param string
     *
     * @return \SyncReaderWriter
     */
    private function getSync($path)
    {
        $key = sha1($this->prefix  . '://' . $path);

        if ( ! isset($this->syncs[$key])) {
            $this->syncs[$key] = new \SyncReaderWriter($key, true);
        }

        return $this->syncs[$key];
    }
}
