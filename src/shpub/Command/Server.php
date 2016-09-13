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
            Log::msg($key);
            if ($verbose) {
                Log::msg('  URL:  ' . $host->server);
                Log::msg('  User: ' . $host->user);
            }
        }
    }
}
?>
