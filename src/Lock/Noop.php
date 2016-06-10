<?php

namespace Twistor\Flysystem\Lock;

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
        return TRUE;
    }

    /**
     * @inheritdoc
     */
    public function acquireWrite($path)
    {
        return TRUE;
    }

    /**
     * @inheritdoc
     */
    public function releaseRead($lock)
    {
    }

    public function releaseWrite($lock)
    {
    }
}
