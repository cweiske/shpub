<?php
namespace shpub;

/**
 * Connect to a micropub server to get an access token.
 *
 * @author  Christian Weiske <cweiske@cweiske.de>
 * @license http://www.gnu.org/licenses/agpl.html GNU AGPL v3
 * @link    http://cweiske.de/shpub.htm
 * @link    http://micropub.net/draft/
 * @link    http://indieweb.org/authorization-endpoint
 */
class Command_Connect
{
    public static $client_id = 'http://cweiske.de/shpub.htm';

    public function __construct(Config $cfg)
    {
        $this->cfg = $cfg;
    }

    public static function opts(\Console_CommandLine $optParser)
    {
        $cmd = $optParser->addCommand('connect');
        $cmd->description = 'Obtain access token from a micropub server';
        $cmd->addOption(
            'force',
            array(
                'short_name'  => '-f',
                'long_name'   => '--force-update',
                'description' => 'Force token update if token already available',
                'action'      => 'StoreTrue',
                'default'     => false,
            )
        );
        $cmd->addOption(
            'scope',
            array(
                'short_name'  => '-s',
                'long_name'   => '--scope',
                'description' => 'Space-separated list of scopes to request'
                    . ' (default: create)',
                'action'      => 'StoreString',
                'default'     => 'create',
            )
        );
        $cmd->addArgument(
            'server',
            [
                'optional'    => false,
                'description' => 'Server URL',
            ]
        );
        $cmd->addArgument(
            'user',
            [
                'optional'    => true,
                'description' => 'User URL',
            ]
        );
        $cmd->addArgument(
            'key',
            [
                'optional'    => true,
                'description' => 'Short name (key)',
            ]
        );
    }

    public function run($server, $user, $newKey, $force, $scope)
    {
        $server = Validator::url($server, 'server');
        if ($user === null) {
            //indieweb: homepage is your identity
            $user = $server;
        } else {
            $user = Validator::url($user, 'user');
        }

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
        Log::msg(
            "To authenticate, open the following URL:\n"
            . $this->getBrowserAuthUrl($host, $user, $redirect_uri, $state, $scope)
        );

        $authParams = $this->startHttpServer($socketStr);
        if ($authParams['state'] != $state) {
            Log::err('Wrong "state" parameter value: ' . $authParams['state']);
            exit(2);
        }
        $code    = $authParams['code'];
        $userUrl = $authParams['me'];

        $accessToken = $this->fetchAccessToken(
            $host, $userUrl, $code, $redirect_uri, $state
        );

        //all fine. update config
        $host->user  = $userUrl;
        $host->token = $accessToken;

        // Now that the token is available, check for a media endpoint
        $host->loadEndpoints(true);

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
        Log::info("Server configuration $hostKey saved successfully.");
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
                    'grant_type'   => 'authorization_code',
                    'me'           => $userUrl,
                    'code'         => $code,
                    'redirect_uri' => $redirect_uri,
                    'client_id'    => static::$client_id,
                    'state'        => $state,
                ]
            )
        );
        $res = $req->send();
        if (intval($res->getStatus() / 100) !== 2) {
            Log::err('Failed to fetch access token');
            Log::err('Server responded with HTTP status code ' . $res->getStatus());
            Log::err($res->getBody());
            exit(2);
        }
        if (Util::getMimeType($res) == 'application/x-www-form-urlencoded') {
            parse_str($res->getBody(), $tokenParams);
        } elseif (Util::getMimeType($res) == 'application/json') {
            $tokenParams = json_decode($res->getBody(), true);
        } else {
            Log::err('Wrong content type in auth verification response');
            exit(2);
        }
        if (!isset($tokenParams['access_token'])) {
            Log::err('"access_token" missing');
            exit(2);
        }

        $accessToken = $tokenParams['access_token'];
        return $accessToken;
    }

    protected function getBrowserAuthUrl($host, $user, $redirect_uri, $state, $scope)
    {
        $sep = strpos($host->endpoints->authorization, '?') === false
            ? '?' : '&';
        return $host->endpoints->authorization
            . $sep . 'me=' . urlencode($user)
            . '&client_id=' . urlencode(static::$client_id)
            . '&redirect_uri=' . urlencode($redirect_uri)
            . '&state=' . urlencode($state)
            . '&scope=' . urlencode($scope)
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
        $ip   = '127.0.0.1';
        $port = 12345;

        if (isset($_SERVER['SSH_CONNECTION'])) {
            $parts = explode(' ', $_SERVER['SSH_CONNECTION']);
            if (count($parts) >= 3) {
                $ip = $parts[2];
            }
        }
        if (strpos($ip, ':') !== false) {
            //ipv6
            $ip = '[' . $ip . ']';
        }

        $redirect_uri = 'http://' . $ip . ':' . $port . '/callback';
        $socketStr    = 'tcp://' . $ip . ':' . $port;
        return [$redirect_uri, $socketStr];
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
