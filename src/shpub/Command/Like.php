<?php
namespace shpub;

class Command_Like
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
        $cmd = $optParser->addCommand('like');
        $cmd->addArgument(
            'url',
            [
                'optional'    => false,
                'description' => 'URL that is liked',
            ]
        );
    }

    public function run($command)
    {
        $url = Validator::url($command->args['url'], 'url');
        if ($url === false) {
            exit(10);
        }

        $body = http_build_query(
            [
                'h'       => 'entry',
                'like-of' => $url,
            ]
        );

        $req = new Request($this->cfg->host, $this->cfg);
        $res = $req->send($body);
        $postUrl = $res->getHeader('Location');
        if ($postUrl === null) {
            Log::err('Error: Server sent no "Location" header and said:');
            Log::err($res->getBody());
            exit(20);
        } else {
            echo "Like created at server\n";
            echo $postUrl . "\n";
        }
    }
}
?>
