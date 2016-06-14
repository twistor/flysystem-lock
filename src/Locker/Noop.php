<?php

namespace Twistor\Flysystem\Locker;

use Twistor\Flysystem\LockerInterface;

/**
 * An no-operation implementation of a locker.
 */
class Noop implements LockerInterface
{
    /**
     * @inheritdoc
     */
    public function acquireRead($path)
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function acquireWrite($path)
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function releaseRead($path, $lock)
    {
    }

    public function releaseWrite($path, $lock)
    {
    }
}
