<?php

namespace Swordapp\Client;

/**
 * A class used to stream large multipart files
 */
class StreamingClass
{
    /**
     * @var resource
     */
    public $data;

    /**
     * @param $handle
     * @param $fd
     * @param int $length
     * @return bool|string
     */
    public function streamFunction($handle, $fd, $length)
    {
        return fread($this->data, $length);
    }
}
