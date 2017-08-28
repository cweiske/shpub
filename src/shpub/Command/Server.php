<?php
namespace shpub;

/**
 * Inspect the list of saved connections/servers.
 *
 * @author  Christian Weiske <cweiske@cweiske.de>
 * @license http://www.gnu.org/licenses/agpl.html GNU AGPL v3
 * @link    http://cweiske.de/shpub.htm
 */
class Command_Server
{
    public function __construct(Config $cfg)
    {
        $this->cfg = $cfg;
    }

    public static function opts(\Console_CommandLine $optParser)
    {
        $cmd = $optParser->addCommand('server');
        $cmd->description = 'List all connections';
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
        $cmd->addArgument(
            'server',
            [
                'default'     => null,
                'optional'    => true,
                'description' => 'Connection name',
            ]
        );
    }

    public function run($server, $verbose)
    {
        if ($server === null) {
            $this->showConnections($verbose);
        } else {
            $this->showConnectionDetails($server, $verbose);
        }
    }

    /**
     * Show a list of all connections
     *
     * @param bool $verbose Show some details
     *
     * @return void
     */
    protected function showConnections($verbose)
    {
        foreach ($this->cfg->hosts as $key => $host) {
            Log::msg($key);
            if ($verbose) {
                Log::msg(' URL:  ' . $host->server);
                Log::msg(' User: ' . $host->user);
            }
        }
    }

    /**
     * Show detailled information for single connection
     *
     * @param string $server  Connection name
     * @param bool   $verbose Show the token
     *
     * @return void
     */
    protected function showConnectionDetails($server, $verbose)
    {
        if (!isset($this->cfg->hosts[$server])) {
            Log::err('Connection does not exist: ' . $server);
            exit(1);
        }

        $host = $this->cfg->hosts[$server];
        Log::msg($server);
        Log::msg(' URL:   ' . $host->server);
        Log::msg(' User:  ' . $host->user);
        if ($verbose) {
            Log::msg(' Token: ' . $host->token);
        }

        Log::msg(' Endpoints:');
        $host->loadEndpoints();
        foreach ($host->endpoints as $key => $value) {
            Log::msg('  ' . str_pad($key . ': ', 15, ' ') . $value);
        }
    }
}
?>
