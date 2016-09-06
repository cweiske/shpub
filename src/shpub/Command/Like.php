<?php
namespace shpub;

class Command_Like
{
    /**
     * @var Config_Host
     */
    protected $host;

    public function __construct($host)
    {
        $this->host = $host;
    }

    public function run($url)
    {
        $url = Validator::url($url, 'url');
        if ($url === false) {
            exit(10);
        }

        $body = http_build_query(
            [
                'h'       => 'entry',
                'like-of' => $url,
            ]
        );

        $req = new Request($this->host);
        $res = $req->send($body);
        $postUrl = $res->getHeader('Location');
        echo "Like created at server\n";
        echo $postUrl . "\n";
    }
}
?>
