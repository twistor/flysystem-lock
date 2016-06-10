<?php

namespace Twistor\Flysystem\Exception;

class UnlockFailedException extends \Exception
{
    /**
     * @var string
     */
    private $path;

    /**
     * Constructs a UnlockFailedException object.
     *
     * @param string     $path
     * @param int        $code
     * @param \Exception $previous
     */
    public function __construct($path, $code = 0, \Exception $previous = null)
    {
        $this->path = $path;

        parent::__construct('Unlock failed at path: ' . $path, $code, $previous);
    }

    /**
     * Returns the path which was not unlockable.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }
}
