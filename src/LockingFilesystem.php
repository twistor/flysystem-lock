<?php

namespace Twistor\Flysystem;

use League\Flysystem\AdapterInterface;
use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use League\Flysystem\Util;

class LockingFilesystem extends Filesystem {

    private $hasLock = false;

    private $locker;

    /**
     * Constructs a LockingFilesystem object.
     *
     * @param AdapterInterface $adapter
     * @param Config|array     $config
     */
    public function __construct(AdapterInterface $adapter, $locker, $config = null)
    {
        $this->locker = $locker;
        parent::__construct($adapter, $config);
    }

    /**
     * @inheritdoc
     */
    public function has($path)
    {
        return $this->withReadLock($path, function() use ($path) {
            return parent::has($path);
        });
    }

    /**
     * @inheritdoc
     */
    public function write($path, $contents, array $config = [])
    {
        return $this->withWriteLock($path, function () use ($path, $contents, $config) {
            return parent::write($path, $contents, $config);
        });
    }

    /**
     * @inheritdoc
     */
    public function writeStream($path, $resource, array $config = [])
    {

        return $this->withWriteLock($path, function () use ($path, $resource, $config) {
            return parent::writeStream($path, $resource, $config);
        });
    }

    /**
     * @inheritdoc
     */
    public function put($path, $contents, array $config = [])
    {
        return $this->withWriteLock($path, function () use ($path, $contents, $config) {
            return parent::put($path, $contents, $config);
        });
    }

    /**
     * @inheritdoc
     */
    public function putStream($path, $resource, array $config = [])
    {
        return $this->withWriteLock($path, function () use ($path, $resource, $config) {
            return parent::putStream($path, $resource, $config);
        });
    }

    /**
     * @inheritdoc
     */
    public function readAndDelete($path)
    {
        return $this->withWriteLock($path, function () use ($path) {
            return parent::readAndDelete($path);
        });
    }

    /**
     * @inheritdoc
     */
    public function update($path, $contents, array $config = [])
    {
        return $this->withWriteLock($path, function () use ($path, $contents, $config) {
            return parent::update($path, $contents, $config);
        });
    }

    /**
     * @inheritdoc
     */
    public function updateStream($path, $resource, array $config = [])
    {
        return $this->withWriteLock($path, function () use ($path, $resource, $config) {
            return parent::updateStream($path, $resource, $config);
        });
    }

    /**
     * @inheritdoc
     */
    public function read($path)
    {
        return $this->withReadLock($path, function () use ($path) {
            return parent::read($path);
        });
    }

    /**
     * @inheritdoc
     */
    public function readStream($path)
    {
        return $this->withReadLock($path, function () use ($path) {
            return parent::readStream($path);
        });
    }

    /**
     * @inheritdoc
     */
    public function rename($path, $newpath)
    {
        $path = Util::normalizePath($path);
        $newpath = Util::normalizePath($newpath);

        try {
            $source = $this->locker->acquireWrite($path);
            $dest = $this->locker->acquireWrite($newpath);

            $this->assertAbsentWithoutLock($path);
            $this->assertPresentWithoutLock($newpath);

            return (bool) $this->getAdapter()->rename($path, $newpath);

        }
        finally {
            isset($source) && $this->locker->release($source);
            isset($dest) && $this->locker->release($dest);
        }
    }

    /**
     * @inheritdoc
     */
    public function copy($path, $newpath)
    {
        $path = Util::normalizePath($path);
        $newpath = Util::normalizePath($newpath);

        try {
            $source = $this->locker->acquireRead($path);
            $dest = $this->locker->acquireWrite($newpath);

            $this->assertAbsentWithoutLock($path);
            $this->assertPresentWithoutLock($newpath);

            return (bool) $this->getAdapter()->copy($path, $newpath);

        } finally {
            isset($source) && $this->locker->release($source);
            isset($dest) && $this->locker->release($dest);
        }
    }

    /**
     * @inheritdoc
     */
    public function delete($path)
    {
        return $this->withWriteLock($path, function () use ($path) {
            return parent::delete($path);
        });
    }

    /**
     * @inheritdoc
     */
    public function deleteDir($dirname)
    {
        return $this->withWriteLock($dirname, function () use ($dirname) {
            return parent::deleteDir($dirname);
        });
    }

    /**
     * @inheritdoc
     */
    public function createDir($dirname, array $config = [])
    {
        return $this->withWriteLock($dirname, function () use ($dirname, $config) {
            return parent::createDir($dirname, $config);
        });
    }

    /**
     * Assert a file is present.
     *
     * @param string $path path to file
     *
     * @throws FileNotFoundException
     */
    private function assertPresentWithoutLock($path)
    {
        if ($path === '') {
            return false;
        }

        if ( ! $this->getAdapter()->has($path)) {
            throw new FileNotFoundException();
        }

        return true;
    }

    /**
     * Assert a file is absent.
     *
     * @param string $path path to file
     *
     * @throws FileExistsException
     */
    private function assertAbsentWithoutLock($path)
    {
        if ($this->getAdapter()->has($path)) {
            throw new FileExistsException();
        }

        return true;
    }

    private function withReadLock($path, callable $callback)
    {
        // Detect recursive locks.
        if ($this->hasLock) {
            return $callback();
        }

        $path = Util::normalizePath($path);

        try {
            $lock = $this->locker->acquireRead($path);
            $this->hasLock = true;

            return $callback();

        } finally {
            isset($lock) && $this->locker->release($lock);
            $this->hasLock = false;
        }
    }

    private function withWriteLock($path, callable $callback)
    {
        // Detect recursive locks.
        if ($this->hasLock) {
            return $callback();
        }

        $path = Util::normalizePath($path);

        try {
            $lock = $this->locker->acquireWrite($path);
            $this->hasLock = true;

            return $callback();

        } finally {
            isset($lock) && $this->locker->release($lock);
            $this->hasLock = false;
        }
    }
}
