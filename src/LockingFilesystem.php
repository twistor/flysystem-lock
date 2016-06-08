<?php

namespace Twistor\Flysystem;

use League\Flysystem\FilesystemInterface;
use League\Flysystem\Handler;
use League\Flysystem\Plugin\PluginNotFoundException;
use League\Flysystem\PluginInterface;
use League\Flysystem\Util;

class LockingFilesystem implements FilesystemInterface
{
    /**
     * The wrapped filesystem.
     *
     * @var \League\Flysystem\FilesystemInterface
     */
    private $filesystem;

    /**
     * The locker.
     *
     * @var \Twistor\Flysystem\LockerInterface
     */
    private $locker;

    /**
     * Constructs a LockingFilesystem object.
     *
     * @param FilesystemInterface $filesystem The wrappeed file system.
     * @param LockerInterface     $locker     The locker.
     */
    public function __construct(FilesystemInterface $filesystem, LockerInterface $locker)
    {
        $this->filesystem = $filesystem;
        $this->locker = $locker;
    }

    /**
     * @inheritdoc
     */
    public function has($path)
    {
        return $this->withReadLock($path, function () use ($path) {
            return $this->filesystem->has($path);
        });
    }

    /**
     * @inheritdoc
     */
    public function write($path, $contents, array $config = [])
    {
        return $this->withWriteLock($path, function () use ($path, $contents, $config) {
            return $this->filesystem->write($path, $contents, $config);
        });
    }

    /**
     * @inheritdoc
     */
    public function writeStream($path, $resource, array $config = [])
    {
        return $this->withWriteLock($path, function () use ($path, $resource, $config) {
            return $this->filesystem->writeStream($path, $resource, $config);
        });
    }

    /**
     * @inheritdoc
     */
    public function put($path, $contents, array $config = [])
    {
        return $this->withWriteLock($path, function () use ($path, $contents, $config) {
            return $this->filesystem->put($path, $contents, $config);
        });
    }

    /**
     * @inheritdoc
     */
    public function putStream($path, $resource, array $config = [])
    {
        return $this->withWriteLock($path, function () use ($path, $resource, $config) {
            return $this->filesystem->putStream($path, $resource, $config);
        });
    }

    /**
     * @inheritdoc
     */
    public function readAndDelete($path)
    {
        return $this->withWriteLock($path, function () use ($path) {
            return $this->filesystem->readAndDelete($path);
        });
    }

    /**
     * @inheritdoc
     */
    public function update($path, $contents, array $config = [])
    {
        return $this->withWriteLock($path, function () use ($path, $contents, $config) {
            return $this->filesystem->update($path, $contents, $config);
        });
    }

    /**
     * @inheritdoc
     */
    public function updateStream($path, $resource, array $config = [])
    {
        return $this->withWriteLock($path, function () use ($path, $resource, $config) {
            return $this->filesystem->updateStream($path, $resource, $config);
        });
    }

    /**
     * @inheritdoc
     */
    public function read($path)
    {
        return $this->withReadLock($path, function () use ($path) {
            return $this->filesystem->read($path);
        });
    }

    /**
     * @inheritdoc
     */
    public function readStream($path)
    {
        return $this->withReadLock($path, function () use ($path) {
            return $this->filesystem->readStream($path);
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

            return $this->filesystem->rename($path, $newpath);
        } finally {
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

            return $this->filesystem->copy($path, $newpath);
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
            return $this->filesystem->delete($path);
        });
    }

    /**
     * @inheritdoc
     */
    public function deleteDir($dirname)
    {
        return $this->withWriteLock($dirname, function () use ($dirname) {
            return $this->filesystem->deleteDir($dirname);
        });
    }

    /**
     * @inheritdoc
     */
    public function createDir($dirname, array $config = [])
    {
        return $this->withWriteLock($dirname, function () use ($dirname, $config) {
            return $this->filesystem->createDir($dirname, $config);
        });
    }

    /**
     * @inheritdoc
     */
    public function listContents($directory = '', $recursive = false)
    {
        // There's not really a good way to lock a directory listing. Files in
        // the directory can appear and disappear during or after listing.
        //
        // Locking the directory at least ensures that it won't disappear out
        // from under us. It's still necessary for the user to check before
        // dealing with the files from a list.
        //
        // @todo Is a read lock helpful, or is it harmful?
        return $this->withReadLock($directory, function () use ($directory, $recursive) {
            return $this->filesystem->listContents($directory, $recursive);
        });
    }

    /**
     * @inheritdoc
     */
    public function getMetadata($path)
    {
        // @todo It's not obvious if locking any of these metadata calls is
        // useful.
        return $this->withReadLock($path, function () use ($path) {
            return $this->filesystem->getMetadata($path);
        });
    }

    /**
     * @inheritdoc
     */
    public function getMimetype($path)
    {
        return $this->withReadLock($path, function () use ($path) {
            return $this->filesystem->getMimetype($path);
        });
    }

    /**
     * @inheritdoc
     */
    public function getSize($path)
    {
        return $this->withReadLock($path, function () use ($path) {
            return $this->filesystem->getSize($path);
        });
    }

    /**
     * @inheritdoc
     */
    public function getTimestamp($path)
    {
        return $this->withReadLock($path, function () use ($path) {
            return $this->filesystem->getTimestamp($path);
        });
    }

    /**
     * @inheritdoc
     */
    public function getVisibility($path)
    {
        return $this->withReadLock($path, function () use ($path) {
            return $this->filesystem->getVisibility($path);
        });
    }

    /**
     * @inheritdoc
     */
    public function setVisibility($path, $visibility)
    {
        return $this->withWriteLock($path, function () use ($path, $visibility) {
            return $this->filesystem->setVisibility($path, $visibility);
        });
    }

    /**
     * @inheritdoc
     */
    public function get($path, Handler $handler = null)
    {
        return $this->withReadLock($path, function () use ($path, $handler) {
            $handler = $this->filesystem->get($path, $handler);

            $handler->setFilesystem($this);

            return $handler;
        });
    }

    /**
     * @inheritdoc
     */
    public function addPlugin(PluginInterface $plugin)
    {
        $this->filesystem->addPlugin($plugin);

        return $this;
    }

    /**
     * Gets the Adapter.
     *
     * @return AdapterInterface adapter
     */
    public function getAdapter()
    {
        return $this->filesystem->getAdapter();
    }

    /**
     * Gets the Config.
     *
     * @return Config config object
     */
    public function getConfig()
    {
        return $this->filesystem->getConfig();
    }

    /**
     * Plugins pass-through.
     *
     * @param string $method
     * @param array  $arguments
     *
     * @throws BadMethodCallException
     *
     * @return mixed
     */
    public function __call($method, array $arguments)
    {
        try {
            return $this->filesystem->invokePlugin($method, $arguments, $this);
        } catch (PluginNotFoundException $e) {
            throw new \BadMethodCallException('Call to undefined method ' . get_class($this->filesystem) . '::' . $method);
        }
    }

    private function withReadLock($path, callable $callback)
    {
        $path = Util::normalizePath($path);

        try {
            $lock = $this->locker->acquireRead($path);

            return $callback();
        } finally {
            isset($lock) && $this->locker->release($lock);
        }
    }

    private function withWriteLock($path, callable $callback)
    {
        $path = Util::normalizePath($path);

        try {
            $lock = $this->locker->acquireWrite($path);

            return $callback();
        } finally {
            isset($lock) && $this->locker->release($lock);
        }
    }
}
