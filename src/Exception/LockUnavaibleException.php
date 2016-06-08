<?php

namespace Twistor\Flysystem\Exception;

class LockUnavaibleException extends \Exception
{
    /**
     * @var string
     */
    private $path;

    /**
     * Constructs a LockUnavaibleException object.
     *
     * @param string     $path
     * @param int        $code
     * @param \Exception $previous
     */
    public function __construct($path, $code = 0, \Exception $previous = null)
    {
        $this->path = $path;

        parent::__construct('Lock unavailable at path: ' . $path, $code, $previous);
    }

    /**
     * Get the path which was not lockable.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }
}
