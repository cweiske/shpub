<?php
namespace shpub;

class Log
{
    public static function err($msg)
    {
        file_put_contents('php://stderr', $msg . "\n", FILE_APPEND);
    }
}
?>
