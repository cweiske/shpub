<?php
namespace shpub;

class Util
{
    /**
     * Get the MIME content type from a HTTP response
     *
     * @param object $res HTTP response
     *
     * @return string MIME type without comments
     */
    public static function getMimeType(\HTTP_Request2_Response $res)
    {
        list($type, ) = explode(';', $res->getHeader('content-type'));
        return trim($type);
    }
}