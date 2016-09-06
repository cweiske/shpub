<?php
namespace shpub;

class Command_Server
{
    public function __construct(Config $cfg)
    {
        $this->cfg = $cfg;
    }

    public function run($verbose)
    {
        foreach ($this->cfg->hosts as $key => $host) {
            echo $key . "\n";
            if ($verbose) {
                echo '  URL:  ' . $host->server . "\n";
                echo '  User: ' . $host->user . "\n";
            }
        }
    }
}
?>
