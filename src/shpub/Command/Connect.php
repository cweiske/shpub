<?php
namespace shpub;

/**
 * @link http://micropub.net/draft/
 * @link http://indieweb.org/authorization-endpoint
 */
class Command_Connect
{
    public static $client_id = 'http://cweiske.de/shpub.htm';

    public function __construct(Config $cfg)
    {
        $this->cfg = $cfg;
    }

    public function run($server, $user, $newKey, $force)
    {
        $host = $this->getHost($newKey != '' ? $newKey : $server, $force);
        if ($host === null) {
            //already taken
            return;
        }
        if ($host->endpoints->incomplete()) {
            $host->server = $server;
            $host->loadEndpoints();
        }

        list($redirect_uri, $socketStr) = $this->getHttpServerData();
        $state = time();
        echo "To authenticate, open the following URL:\n"
            . $this->getBrowserAuthUrl($host, $user, $redirect_uri, $state)
            . "\n";

        $authParams = $this->startHttpServer($socketStr);
        if ($authParams['state'] != $state) {
            Log::err('Wrong "state" parameter value: ' . $authParams['state']);
            exit(2);
        }
        $code    = $authParams['code'];
        $userUrl = $authParams['me'];
        $this->verifyAuthCode($host, $code, $state, $redirect_uri, $userUrl);

        $accessToken = $this->fetchAccessToken(
            $host, $userUrl, $code, $redirect_uri, $state
        );

        //all fine. update config
        $host->user  = $userUrl;
        $host->token = $accessToken;

        if ($newKey != '') {
            $hostKey = $newKey;
        } else {
            $hostKey = $this->cfg->getHostByName($server);
            if ($hostKey === null) {
                $keyBase = parse_url($host->server, PHP_URL_HOST);
                $newKey  = $keyBase;
                $count = 0;
                while (isset($this->cfg->hosts[$newKey])) {
                    $newKey = $keyBase . ++$count;
                }
                $hostKey = $newKey;
            }
        }
        $this->cfg->hosts[$hostKey] = $host;
        $this->cfg->save();
    }

    protected function fetchAccessToken(
        $host, $userUrl, $code, $redirect_uri, $state
    ) {
        $req = new \HTTP_Request2($host->endpoints->token, 'POST');
        if (version_compare(PHP_VERSION, '5.6.0', '<')) {
            //correct ssl validation on php 5.5 is a pain, so disable
            $req->setConfig('ssl_verify_host', false);
            $req->setConfig('ssl_verify_peer', false);
        }
        $req->setHeader('Content-Type: application/x-www-form-urlencoded');
        $req->setBody(
            http_build_query(
                [
                    'me'           => $userUrl,
                    'code'         => $code,
                    'redirect_uri' => $redirect_uri,
                    'client_id'    => static::$client_id,
                    'state'        => $state,
                ]
            )
        );
        $res = $req->send();
        if ($res->getHeader('content-type') != 'application/x-www-form-urlencoded') {
            Log::err('Wrong content type in auth verification response');
            exit(2);
        }
        parse_str($res->getBody(), $tokenParams);
        if (!isset($tokenParams['access_token'])) {
            Log::err('"access_token" missing');
            exit(2);
        }

        $accessToken = $tokenParams['access_token'];
        return $accessToken;
    }

    protected function getBrowserAuthUrl($host, $user, $redirect_uri, $state)
    {
        return $host->endpoints->authorization
            . '?me=' . urlencode($user)
            . '&client_id=' . urlencode(static::$client_id)
            . '&redirect_uri=' . urlencode($redirect_uri)
            . '&state=' . $state
            . '&scope=post'
            . '&response_type=code';
    }

    protected function getHost($keyOrServer, $force)
    {
        $host = new Config_Host();
        $key = $this->cfg->getHostByName($keyOrServer);
        if ($key !== null) {
            $host = $this->cfg->hosts[$key];
            if (!$force && $host->token != '') {
                Log::err('Token already available');
                return;
            }
        }
        return $host;
    }

    protected function getHttpServerData()
    {
        //FIXME: get IP from SSH_CONNECTION
        $ip   = '127.0.0.1';
        $port = 12345;
        $redirect_uri = 'http://' . $ip . ':' . $port . '/callback';
        $socketStr    = 'tcp://' . $ip . ':' . $port;
        return [$redirect_uri, $socketStr];
    }

    protected function verifyAuthCode($host, $code, $state, $redirect_uri, $me)
    {
        $req = new \HTTP_Request2($host->endpoints->authorization, 'POST');
        if (version_compare(PHP_VERSION, '5.6.0', '<')) {
            //correct ssl validation on php 5.5 is a pain, so disable
            $req->setConfig('ssl_verify_host', false);
            $req->setConfig('ssl_verify_peer', false);
        }
        $req->setHeader('Content-Type: application/x-www-form-urlencoded');
        $req->setBody(
            http_build_query(
                [
                    'code'         => $code,
                    'state'        => $state,
                    'client_id'    => static::$client_id,
                    'redirect_uri' => $redirect_uri,
                ]
            )
        );
        $res = $req->send();
        if ($res->getHeader('content-type') != 'application/x-www-form-urlencoded') {
            Log::err('Wrong content type in auth verification response');
            exit(2);
        }
        parse_str($res->getBody(), $verifiedParams);
        if (!isset($verifiedParams['me'])
            || $verifiedParams['me'] !== $me
        ) {
            Log::err('Non-matching "me" values');
            exit(2);
        }
    }

    protected function startHttpServer($socketStr)
    {
        $responseOk = "HTTP/1.0 200 OK\r\n"
            . "Content-Type: text/plain\r\n"
            . "\r\n"
            . "Ok. You may close this tab and return to the shell.\r\n";
        $responseErr = "HTTP/1.0 400 Bad Request\r\n"
            . "Content-Type: text/plain\r\n"
            . "\r\n"
            . "Bad Request\r\n";

        //5 minutes should be enough for the user to confirm
        ini_set('default_socket_timeout', 60 * 5);
        $server = stream_socket_server($socketStr, $errno, $errstr);
        if (!$server) {
            Log::err('Error starting HTTP server');
            return false;
        }

        do {
            $sock = stream_socket_accept($server);
            if (!$sock) {
                Log::err('Error accepting socket connection');
                exit(1);
            }

            $headers = [];
            $body    = null;
            $content_length = 0;
            //read request headers
            while (false !== ($line = trim(fgets($sock)))) {
                if ('' === $line) {
                    break;
                }
                $regex = '#^Content-Length:\s*([[:digit:]]+)\s*$#i';
                if (preg_match($regex, $line, $matches)) {
                    $content_length = (int) $matches[1];
                }
                $headers[] = $line;
            }

            // read content/body
            if ($content_length > 0) {
                $body = fread($sock, $content_length);
            }

            // send response
            list($method, $url, $httpver) = explode(' ', $headers[0]);
            if ($method == 'GET') {
                $parts = parse_url($url);
                if (isset($parts['path']) && $parts['path'] == '/callback'
                    && isset($parts['query'])
                ) {
                    parse_str($parts['query'], $query);
                    if (isset($query['code'])
                        && isset($query['state'])
                        && isset($query['me'])
                    ) {
                        fwrite($sock, $responseOk);
                        fclose($sock);
                        return $query;
                    }
                }
            }

            fwrite($sock, $responseErr);
            fclose($sock);
        } while (true);
    }
}
?>
