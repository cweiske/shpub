<?php
namespace shpub;

class Command_Note
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
        $cmd->addArgument(
            'text',
            [
                'optional'    => false,
                'multiple'    => false,
                'description' => 'Post text',
            ]
        );
    }

    public function run($command)
    {
        $data = [
            'h'       => 'entry',
            'content' => $command->args['text'],
        ];

        $body = http_build_query($data);

        $req = new Request($this->cfg->host, $this->cfg);
        $res = $req->send($body);
        $postUrl = $res->getHeader('Location');
        echo "Post created at server\n";
        echo $postUrl . "\n";
    }
}
?>
