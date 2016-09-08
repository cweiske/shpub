<?php
namespace shpub;

class Command_Reply
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
                'multiple'    => true,
                'description' => 'Reply text',
            ]
        );
    }
    public function run($url, $text)
    {
        $url = Validator::url($url, 'url');
        if ($url === false) {
            exit(10);
        }

        $body = http_build_query(
            [
                'h'           => 'entry',
                'content'     => $text,
                'in-reply-to' => $url,
            ]
        );

        $req = new Request($this->cfg->host, $this->cfg);
        $res = $req->send($body);
        $postUrl = $res->getHeader('Location');
        echo "Reply created at server\n";
        echo $postUrl . "\n";
    }
}
?>
