<?php
namespace shpub;

class Request
{
    public $req;
    public $cfg;

    protected $sendAsJson = false;
    protected $uploadsInfo = [];
    protected $dedicatedBody = false;

    protected $properties = [];
    protected $type = null;
    protected $action = null;
    protected $url = null;

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
        if ($this->sendAsJson) {
            //application/json
            if ($body !== null) {
                throw new \Exception('body already defined');
            }
            $this->req->setHeader('Content-Type: application/json');
            $data = [];
            if ($this->action !== null) {
                $data['action'] = $this->action;
            }
            if ($this->url !== null) {
                $data['url'] = $this->url;
            }
            if ($this->type !== null) {
                $data['type'] = 'h-' . $this->type;
            }
            if (count($this->properties)) {
                $data['properties'] = $this->properties;
            }
            $body = json_encode($data);
        } else {
            //form-encoded
            if ($this->type !== null) {
                $this->req->addPostParameter('h', $this->type);
            }
            if ($this->action !== null) {
                $this->req->addPostParameter('action', $this->action);
            }
            if ($this->url !== null) {
                $this->req->addPostParameter('url', $this->url);
            }
            foreach ($this->properties as $propkey => $propval) {
                if (isset($propval['html'])) {
                    //workaround for content[html]
                    $propkey = $propkey . '[html]';
                    $propval = $propval['html'];
                } else if (count($propval) == 1) {
                    $propval = reset($propval);
                }
                $this->req->addPostParameter($propkey, $propval);
            }
        }

        if ($body !== null) {
            $this->dedicatedBody = true;
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

    public function setAction($action)
    {
        $this->action = $action;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @param string                $fieldName name of file-upload field
     * @param string|resource|array $filename  full name of local file,
     *               pointer to open file or an array of files
     */
    public function addUpload($fieldName, $filename)
    {
        if ($this->sendAsJson) {
            throw new \Exception('File uploads do not work with JSON');
        }
        $this->uploadsInfo[$fieldName] = $filename;
        return $this->req->addUpload($fieldName, $filename);
    }

    public function addContent($text, $isHtml)
    {
        if ($isHtml) {
            $this->addProperty(
                'content', ['html' => $text]
            );
        } else {
            $this->addProperty('content', $text);
        }

    }

    /**
     * Adds a micropub property to the request.
     *
     * @param string       $key    Parameter name
     * @param string|array $values One or multiple values
     */
    public function addProperty($key, $values)
    {
        $this->properties[$key] = (array) $values;
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

        if ($this->dedicatedBody) {
            $command .= ' --data ' . escapeshellarg($this->req->getBody());
        }

        $command .= ' ' . escapeshellarg((string) $this->req->getUrl());

        Log::msg($command);
    }

    public function setSendAsJson($json)
    {
        $this->sendAsJson = $json;
    }
}