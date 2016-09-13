<?php
namespace shpub;

class Command_Delete extends Command_AbstractProps
{
    /**
     * @var Config
     */
    protected $cfg;

    public function __construct($cfg)
    {
        $this->cfg = $cfg;
    }

    public static function opts(\Console_CommandLine $optParser)
    {
        $cmd = $optParser->addCommand('delete');
        $cmd->addArgument(
            'url',
            [
                'optional'    => false,
                'description' => 'URL to remove',
            ]
        );
    }

    public function run(\Console_CommandLine_Result $cmdRes)
    {
        $url = Validator::url($cmdRes->args['url'], 'url');
        if ($url === false) {
            exit(10);
        }

        $req = new Request($this->cfg->host, $this->cfg);
        $req->req->addPostParameter('action', 'delete');
        $req->req->addPostParameter('url', $url);

        $res = $req->send();
        Log::info('Post deleted from server');
    }
}
?>
