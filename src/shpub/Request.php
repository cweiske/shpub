<?php
namespace shpub;

class Request
{
    public $req;
    public $cfg;

    protected $uploadsInfo = [];

    public function __construct($host, $cfg)
    {
        $this->cfg = $cfg;
        $this->req = new MyHttpRequest2($host->endpoints->micropub, 'POST');
        $this->req->setHeader('User-Agent: shpub');
        if (version_compare(PHP_VERSION, '5.6.0', '<')) {
            //correct ssl validation on php 5.5 is a pain, so disable
            $this->req->setConfig('ssl_verify_host', false);
            $this->req->setConfig('ssl_verify_peer', false);
        }
        $this->req->setHeader('Content-type', 'application/x-www-form-urlencoded');
        $this->req->setHeader('authorization', 'Bearer ' . $host->token);
    }

    public function send($body = null)
    {
        if ($body !== null) {
            $this->req->setBody($body);
        }
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

    /**
     * @param string                $fieldName    name of file-upload field
     * @param string|resource|array $filename     full name of local file,
     *               pointer to open file or an array of files
     */
    public function addUpload($fieldName, $filename)
    {
        $this->uploadsInfo[$fieldName] = $filename;
        return $this->req->addUpload($fieldName, $filename);
    }

    /**
     * Add one or multiple POST parameters.
     * Automatically adds them as array or as string.
     *
     * @param string       $key    Parameter name
     * @param string|array $values One or multiple values
     */
    public function addPostParameter($key, $values)
    {
        if (count($values) == 1) {
            $values = reset($values);
        }
        $this->req->addPostParameter($key, $values);
    }

    protected function printCurl()
    {
        $command = 'curl';
        if ($this->req->getMethod() != 'GET') {
            $command .= ' -X ' . $this->req->getMethod();
        }
        foreach ($this->req->getHeaders() as $key => $val) {
            $caseKey = implode('-', array_map('ucfirst', explode('-', $key)));
            $command .= ' -H ' . escapeshellarg($caseKey . ': ' . $val);
        }

        $postParams = $this->req->getPostParams();

        if (count($this->uploadsInfo) == 0) {
            foreach ($postParams as $k => $v) {
                if (!is_array($v)) {
                    $command .= ' -d ' . escapeshellarg($k . '=' . $v);
                } else {
                    foreach ($v as $ak => $av) {
                        $command .= ' -d ' . escapeshellarg(
                            $k . '[' . $ak . ']=' . $av
                        );
                    }
                }
            }
        } else {
            foreach ($postParams as $k => $v) {
                $command .= ' -F ' . escapeshellarg($k . '=' . $v);
            }
            foreach ($this->uploadsInfo as $fieldName => $filename) {
                if (!is_array($filename)) {
                    $command .= ' -F ' . escapeshellarg(
                        $fieldName . '=@' . $filename
                    );
                } else {
                    foreach ($filename as $k => $realFilename) {
                        $command .= ' -F ' . escapeshellarg(
                            $fieldName . '[' . $k . ']=@' . $realFilename
                        );
                    }
                }
            }
        }

        $command .= ' ' . escapeshellarg((string) $this->req->getUrl());

        echo $command . "\n";
    }
}