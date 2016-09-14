<?php
namespace shpub;

class Cli
{
    /**
     * @var Config
     */
    protected $cfg;

    public function run()
    {
        $this->cfg = new Config();
        $this->cfg->load();

        try {
            $optParser = $this->loadOptParser();
            $res = $this->parseParameters($optParser);

            switch ($res->command_name) {
            case 'connect':
                $cmd = new Command_Connect($this->cfg);
                $cmd->run(
                    $res->command->args['server'],
                    $res->command->args['user'],
                    $res->command->args['key'],
                    $res->command->options['force']
                );
                break;

            case 'server':
                $cmd = new Command_Server($this->cfg);
                $cmd->run($res->command->options['verbose']);
                break;

            default:
                $class = 'shpub\\Command_' . ucfirst($res->command_name);
                $this->requireValidHost();
                $cmd = new $class($this->cfg);
                $cmd->run($res->command);
                break;
            }
        } catch (\Exception $e) {
            Log::err('Error: ' . $e->getMessage());
            exit(1);
        }
    }

    /**
     * Let the CLI option parser parse the options.
     *
     * @param object $parser Option parser
     *
     * @return object Parsed command line parameters
     */
    protected function parseParameters(\Console_CommandLine $optParser)
    {
        try {
            $res  = $optParser->parse();
            $opts = $res->options;

            $this->cfg->host = new Config_Host();
            if ($opts['server'] !== null) {
                $key = $this->cfg->getHostByName($opts['server']);
                if ($key === null) {
                    $this->cfg->host->server = $opts['server'];
                } else {
                    $this->cfg->host = $this->cfg->hosts[$key];
                }
            } else {
                $key = $this->cfg->getDefaultHost();
                if ($key !== null) {
                    $this->cfg->host = $this->cfg->hosts[$key];
                }
            }
            $this->cfg->setDebug($opts['debug']);

            return $res;
        } catch (\Exception $exc) {
            $optParser->displayError($exc->getMessage());
        }
    }

    /**
     * Load parameters for the CLI option parser.
     *
     * @return \Console_CommandLine CLI option parser
     */
    protected function loadOptParser()
    {
        $optParser = new \Console_CommandLine();
        $optParser->description = 'shpub';
        $optParser->version = '0.0.5';
        $optParser->subcommand_required = true;

        $optParser->addOption(
            'server',
            array(
                'short_name'  => '-s',
                'long_name'   => '--server',
                'description' => 'Server URL',
                'help_name'   => 'URL',
                'action'      => 'StoreString',
                'default'     => null,
            )
        );
        $optParser->addOption(
            'debug',
            array(
                'short_name'  => '-d',
                'long_name'   => '--debug',
                'description' => 'Verbose output',
                'action'      => 'StoreTrue',
                'default'     => false,
            )
        );

        $cmd = $optParser->addCommand('connect');
        $cmd->addOption(
            'force',
            array(
                'short_name'  => '-f',
                'long_name'   => '--force-update',
                'description' => 'Force token update if token already available',
                'action'      => 'StoreTrue',
                'default'     => false,
            )
        );
        $cmd->addArgument(
            'server',
            [
                'optional'    => false,
                'description' => 'Server URL',
            ]
        );
        $cmd->addArgument(
            'user',
            [
                'optional'    => true,
                'description' => 'User URL',
            ]
        );
        $cmd->addArgument(
            'key',
            [
                'optional'    => true,
                'description' => 'Short name (key)',
            ]
        );

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

        Command_Article::opts($optParser);
        Command_Note::opts($optParser);
        Command_Reply::opts($optParser);
        Command_Like::opts($optParser);
        Command_Repost::opts($optParser);

        Command_Delete::opts($optParser);

        return $optParser;
    }

    protected function requireValidHost()
    {
        if ($this->cfg->host->server === null
            || $this->cfg->host->user === null
            || $this->cfg->host->token === null
        ) {
            throw new \Exception(
                'Server data incomplete. "shpub connect" first.'
            );
        }

        $this->cfg->host->loadEndpoints();
    }
}
?>
