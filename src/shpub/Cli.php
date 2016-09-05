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
                    $res->command->args['key']
                );
                break;
            case 'like':
                $this->requireValidHost();
                $cmd = new Command_Like($this->cfg->host);
                $cmd->run($res->command->args['url']);
                break;
            default:
                var_dump($this->cfg->host, $res);
                Log::err('FIXME');
            }
        } catch (\Exception $e) {
            echo 'Error: ' . $e->getMessage() . "\n";
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
            }
            if ($opts['user'] !== null) {
                $this->cfg->host->user = $opts['user'];
            }

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
        $optParser->version = '0.0.0';
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
            'user',
            array(
                'short_name'  => '-u',
                'long_name'   => '--user',
                'description' => 'User URL',
                'help_name'   => 'URL',
                'action'      => 'StoreString',
                'default'     => null,
            )
        );

        $cmd = $optParser->addCommand('connect');
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
                'optional'    => false,
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

        //$cmd = $optParser->addCommand('post');
        $cmd = $optParser->addCommand('reply');
        $cmd->addArgument(
            'url',
            [
                'optional'    => false,
                'description' => 'URL that is replied to',
            ]
        );
        $cmd->addArgument(
            'text',
            [
                'optional'    => false,
                'description' => 'Reply text',
            ]
        );

        $cmd = $optParser->addCommand('like');
        $cmd->addArgument(
            'url',
            [
                'optional'    => false,
                'description' => 'URL that is liked',
            ]
        );

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
    }
}
?>
