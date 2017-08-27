<?php
namespace shpub;

class Request
{
    public $req;
    public $cfg;
    public $host;

    protected $sendAsJson = false;
    protected $directUpload = false;
    protected $uploadsInfo = [];
    protected $dedicatedBody = false;

    protected $properties = [];
    protected $type = null;
    protected $action = null;
    protected $url = null;

    public function __construct($host, $cfg)
    {
        $this->cfg  = $cfg;
        $this->host = $host;
        $this->req = $this->getHttpRequest(
            $this->host->endpoints->micropub, $this->host->token
        );
    }

    protected function getHttpRequest($url, $accessToken)
    {
        $req = new MyHttpRequest2($url, 'POST');
        $req->setHeader('User-Agent: shpub');
        if (version_compare(PHP_VERSION, '5.6.0', '<')) {
            //correct ssl validation on php 5.5 is a pain, so disable
            $req->setConfig('ssl_verify_host', false);
            $req->setConfig('ssl_verify_peer', false);
        }
        $req->setHeader('Content-type', 'application/x-www-form-urlencoded');
        $req->setHeader('authorization', 'Bearer ' . $accessToken);
        return $req;
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
                $data['type'] = array('h-' . $this->type);
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
            $cp = new CurlPrinter();
            $cp->show($this->req, $this->uploadsInfo, $this->dedicatedBody);
        }
        $res = $this->req->send();

        if (intval($res->getStatus() / 100) != 2) {
            $this->displayErrorResponse($res);
        }
        return $res;
    }

    protected function displayErrorResponse($res)
    {
        Log::err(
            'Server returned an error status code ' . $res->getStatus()
        );

        $shown = false;
        if (Util::getMimeType($res) == 'application/json') {
            $errData = json_decode($res->getBody());
            if (!isset($errData->error)) {
                Log::err('Error response does not contain "error" property');
            } else if (isset($errData->error_description)) {
                Log::err($errData->error . ': ' . $errData->error_description);
                $shown = true;
            } else {
                Log::err($errData->error);
                $shown = true;
            }
        }

        if (!$shown) {
            Log::err($res->getBody());
        }
        exit(11);
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
     * Add file upload
     *
     * @param string $fieldName name of file-upload field
     * @param array  $fileNames list of local file paths
     *
     * @return void
     */
    public function addUpload($fieldName, $fileNames)
    {
        if ($this->directUpload && $this->sendAsJson) {
            throw new \Exception(
                'Cannot do direct upload with JSON requests'
            );
        }

        if ($this->host->endpoints->media === null
            || $this->directUpload
        ) {
            if ($this->sendAsJson) {
                throw new \Exception(
                    'No media endpoint available, which is required for JSON'
                );
            }
            if (count($fileNames) == 1) {
                $fileNames = reset($fileNames);
            }
            $this->uploadsInfo[$fieldName] = $fileNames;
            return $this->req->addUpload($fieldName, $fileNames);
        }

        $urls = [];
        foreach ($fileNames as $fileName) {
            $urls[] = $this->uploadToMediaEndpoint($fileName);
        }
        if (count($urls) == 1) {
            $urls = reset($urls);
        }
        $this->addProperty($fieldName, $urls);
    }

    /**
     * Execute the file upload
     *
     * @param string $fileName File path
     *
     * @return string URL at media endpoint
     */
    public function uploadToMediaEndpoint($fileName)
    {
        $httpReq = $this->getHttpRequest(
            $this->host->endpoints->media, $this->host->token
        );
        $httpReq->addUpload('file', $fileName);

        if ($this->cfg->debug) {
            $cp = new CurlPrinter();
            $cp->show($httpReq, ['file' => $fileName]);
        }
        $res = $httpReq->send();
        if (intval($res->getStatus() / 100) != 2) {
            Log::err(
                'Media endpoint returned an error status code '
                . $res->getStatus()
            );
            Log::err($res->getBody());
            exit(11);
        }

        $location = $res->getHeader('location');
        if ($location === null) {
            Log::err('Media endpoint did not return a URL');
            exit(11);
        }

        $base = new \Net_URL2($this->host->endpoints->media);
        return (string) $base->resolve($location);
    }

    public function addContent($text, $isHtml)
    {
        if ($isHtml) {
            $this->addProperty(
                'content', [['html' => $text]]
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
     *
     * @return void
     */
    public function addProperty($key, $values)
    {
        $this->properties[$key] = (array) $values;
    }

    public function setSendAsJson($json)
    {
        $this->sendAsJson = $json;
    }

    public function setDirectUpload($directUpload)
    {
        $this->directUpload = $directUpload;
    }
}