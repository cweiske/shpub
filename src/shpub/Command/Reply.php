<?php
namespace shpub;

class Command_Reply
{
    /**
     * @var Config_Host
     */
    protected $host;

    public function __construct($host)
    {
        $this->host = $host;
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

        $req = new Request($this->host);
        $res = $req->send($body);
        $postUrl = $res->getHeader('Location');
        echo "Reply created at server\n";
        echo $postUrl . "\n";
    }
}
?>
