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
    var $data;

    /**
     * @param $handle
     * @param $fd
     * @param int $length
     * @return bool|string
     */
    function stream_function($handle, $fd, $length)
    {
        return fread($this->data, $length);
    }
}

