<?php
namespace shpub;

class Log
{
    public static function info($msg)
    {
        echo $msg . "\n";
    }

    public static function msg($msg)
    {
        echo $msg . "\n";
    }

    public static function err($msg)
    {
        file_put_contents('php://stderr', $msg . "\n", FILE_APPEND);
    }
}
?>
