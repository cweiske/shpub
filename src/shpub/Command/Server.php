<?php
namespace shpub;

class Command_Server
{
    public function __construct(Config $cfg)
    {
        $this->cfg = $cfg;
    }

    public static function opts(\Console_CommandLine $optParser)
    {
        $cmd = $optParser->addCommand('server');
        $cmd->addOption(
            'verbose',
            array(
                'short_name'  => '-v',
                'long_name'   => '--verbose',
                'description' => 'Show more server infos',
                'action'      => 'StoreTrue',
                'default'     => false,
            )
        );
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
