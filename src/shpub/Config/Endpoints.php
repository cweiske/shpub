<?php
namespace shpub;

class Config_Endpoints
{
    /**
     * Micropub endpoint URL
     *
     * @var string
     */
    public $micropub;

    /**
     * Micropub media endpoint URL
     *
     * @var string
     */
    public $media;

    /**
     * Access token endpoint URL
     *
     * @var string
     */
    public $token;

    /**
     * Authorization endpoint URL
     *
     * @var string
     */
    public $authorization;

    public function incomplete()
    {
        return $this->authorization === null
            || $this->token === null
            || $this->micropub === null;
    }

    public function discover($server)
    {
        //TODO: discovery via link headers
        $baseUrl = new \Net_URL2($server);

        $doc = new \DOMDocument();
        @$doc->loadHTMLFile($server);
        $sx = simplexml_import_dom($doc);
        if ($sx === false) {
            Log::err('Error loading URL: ' . $server);
            exit(1);
        }
        //$sx->registerXPathNamespace('h', 'http://www.w3.org/1999/xhtml');

        $auths = $sx->xpath(
            '/html/head/link[@rel="authorization_endpoint" and @href]'
        );
        if (!count($auths)) {
            Log::err('No authorization endpoint found at ' . $server);
            exit(1);
        }
        $this->authorization = (string) $baseUrl->resolve(
            (string) $auths[0]['href']
        );

        $tokens = $sx->xpath(
            '/html/head/link[@rel="token_endpoint" and @href]'
        );
        if (!count($tokens)) {
            Log::err('No token endpoint found');
            exit(1);
        }
        $this->token = (string) $baseUrl->resolve(
            (string) $tokens[0]['href']
        );

        $mps = $sx->xpath(
            '/html/head/link[@rel="micropub" and @href]'
        );
        if (!count($mps)) {
            Log::err('No micropub endpoint found');
            exit(1);
        }
        $this->micropub = (string) $baseUrl->resolve(
            (string) $mps[0]['href']
        );
    }

    public function load($server)
    {
        $file = $this->getCacheFilePath($server, false);
        if ($file === false || !file_exists($file)) {
            return false;
        }
        $data = parse_ini_file($file);
        foreach ($data as $prop => $val) {
            if (!property_exists($this, $prop)) {
                Log::err('Invalid cache config key "' . $prop . '"');
                exit(1);
            }
            $this->$prop = $val;
        }
        return true;
    }

    public function save($server)
    {
        $file = $this->getCacheFilePath($server, true);
        if ($file === false) {
            return false;
        }

        file_put_contents(
            $file,
            'micropub=' . Config::quoteIniValue($this->micropub) . "\n"
            . 'media=' . Config::quoteIniValue($this->media) . "\n"
            . 'token=' . Config::quoteIniValue($this->token) . "\n"
            . 'authorization='
            . Config::quoteIniValue($this->authorization) . "\n"
        );
    }

    public function getCacheFilePath($server, $create = false)
    {
        if (isset($_SERVER['XDG_CACHE_HOME'])
            && $_SERVER['XDG_CACHE_HOME'] != ''
        ) {
            $cacheBaseDir = $_SERVER['XDG_CACHE_HOME'];
        } else {
            if (!isset($_SERVER['HOME']) || $_SERVER['HOME'] == '') {
                Log::err('Cannot determine home directory');
                return false;
            }
            $cacheBaseDir = $_SERVER['HOME'] . '/.cache';
        }

        $cacheDir = $cacheBaseDir . '/shpub';
        if (!is_dir($cacheDir) && $create) {
            mkdir($cacheDir, 0700, true);
        }
        $file = $cacheDir . '/' . urlencode($server) . '.ini';
        return $file;
    }
}
?>
