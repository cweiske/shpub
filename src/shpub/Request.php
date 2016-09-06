<?php
namespace shpub;

class Request
{
    public $req;

    public function __construct($host)
    {
        $this->req = new \HTTP_Request2($host->endpoints->micropub, 'POST');
        if (version_compare(PHP_VERSION, '5.6.0', '<')) {
            //correct ssl validation on php 5.5 is a pain, so disable
            $this->req->setConfig('ssl_verify_host', false);
            $this->req->setConfig('ssl_verify_peer', false);
        }
        $this->req->setHeader('Content-type', 'application/x-www-form-urlencoded');
        $this->req->setHeader('Authorization', 'Bearer ' . $host->token);
    }

    public function send($body)
    {
        $this->req->setBody($body);
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
}