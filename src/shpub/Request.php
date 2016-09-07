<?php
namespace shpub;

class Request
{
    public $req;
    public $cfg;

    public function __construct($host, $cfg)
    {
        $this->cfg = $cfg;
        $this->req = new \HTTP_Request2($host->endpoints->micropub, 'POST');
        $this->req->setHeader('User-Agent: shpub');
        if (version_compare(PHP_VERSION, '5.6.0', '<')) {
            //correct ssl validation on php 5.5 is a pain, so disable
            $this->req->setConfig('ssl_verify_host', false);
            $this->req->setConfig('ssl_verify_peer', false);
        }
        $this->req->setHeader('Content-type', 'application/x-www-form-urlencoded');
        $this->req->setHeader('authorization', 'Bearer ' . $host->token);
    }

    public function send($body)
    {
        $this->req->setBody($body);
        if ($this->cfg->debug) {
            $this->printCurl();
        }
        $res = $this->req->send();

        if (intval($res->getStatus() / 100) != 2) {
            Log::err(
                'Server returned an error status code ' . $res->getStatus()
            );
            Log::err($res->getBody());
            exit(11);
        }
        return $res;
    }

    protected function printCurl()
    {
        $command = 'curl';
        if ($this->req->getMethod() != 'GET') {
            $command .= ' -X ' . $this->req->getMethod();
        }
        foreach ($this->req->getHeaders() as $key => $val) {
            $command .= ' -H ' . escapeshellarg($key . ': ' . $val);
        }
        $command .= ' --data ' . escapeshellarg($this->req->getBody());
        $command .= ' ' . escapeshellarg((string) $this->req->getUrl());

        echo $command . "\n";
    }
}