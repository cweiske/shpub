<?php
namespace shpub;

class Command_Note extends Command_AbstractProps
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
        $cmd = $optParser->addCommand('note');
        static::optsGeneric($cmd);
        $cmd->addArgument(
            'text',
            [
                'optional'    => false,
                'multiple'    => false,
                'description' => 'Post text',
            ]
        );
    }

    public function run(\Console_CommandLine_Result $cmdRes)
    {
        $req = new Request($this->cfg->host, $this->cfg);
        $req->req->addPostParameter('h', 'entry');
        $req->req->addPostParameter('content', $cmdRes->args['text']);
        $this->handleGenericOptions($cmdRes, $req);

        $res = $req->send();
        $postUrl = $res->getHeader('Location');
        echo "Post created at server\n";
        echo $postUrl . "\n";
    }
}
?>
