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

        $req = new \HTTP_Request2($this->host->endpoints->micropub, 'POST');
        if (version_compare(PHP_VERSION, '5.6.0', '<')) {
            //correct ssl validation on php 5.5 is a pain, so disable
            $req->setConfig('ssl_verify_host', false);
            $req->setConfig('ssl_verify_peer', false);
        }
        $req->setHeader('Content-type', 'application/x-www-form-urlencoded');
        $req->setHeader('Authorization', 'Bearer ' . $this->host->token);
        $req->setBody($body);
        $res = $req->send();
        if (intval($res->getStatus() / 100) != 2) {
            Log::err(
                'Server returned an error status code ' . $res->getStatus()
            );
            Log::err($res->getBody());
            exit(11);
        }
        $postUrl = $res->getHeader('Location');
        echo "Like created at server\n";
        echo $postUrl . "\n";
    }
}
?>
