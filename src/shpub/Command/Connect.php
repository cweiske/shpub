<?php
namespace shpub;

class Command_Connect
{
    public function __construct(Config $cfg)
    {
        $this->cfg = $cfg;
    }

    public function run($server, $user, $key)
    {
    }
}
?>
