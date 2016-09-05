<?php
namespace shpub;

if (file_exists(__DIR__ . '/../src/shpub/Autoloader.php')) {
    include_once __DIR__ . '/../src/shpub/Autoloader.php';
    Autoloader::register();
}
$cli = new Cli();
$cli->run();
exit();
/**
 * @link http://micropub.net/draft/
 * @link http://indieweb.org/authorization-endpoint
 */
$server = 'http://anoweco.bogo/';
$user   = 'http://anoweco.bogo/user/3.htm';

require_once 'HTTP/Request2.php';

$endpoints = discoverEndpoints($server);
list($accessToken, $userUrl) = getAuthCode($user, $endpoints);
var_dump($endpoints, $accessToken, $userUrl);


function getAuthCode($user, $endpoints)
{
    //fetch temporary authorization token
    $redirect_uri = 'http://127.0.0.1:12345/callback';
    $state        = time();
    $client_id    = 'http://cweiske.de/shpub.htm';

    $browserUrl = $endpoints->authorization
        . '?me=' . urlencode($user)
        . '&client_id=' . urlencode($client_id)
        . '&redirect_uri=' . urlencode($redirect_uri)
        . '&state=' . $state
        . '&scope=post'
        . '&response_type=code';
    echo "To authenticate, open the following URL:\n"
        . $browserUrl . "\n";

    $authParams = startHttpServer();

    if ($authParams['state'] != $state) {
        logError('Wrong "state" parameter value');
        exit(2);
    }

    //verify indieauth params
    $req = new HTTP_Request2($endpoints->authorization, 'POST');
    $req->setHeader('Content-Type: application/x-www-form-urlencoded');
    $req->setBody(
        http_build_query(
            [
                'code'         => $authParams['code'],
                'state'        => $state,
                'client_id'    => $client_id,
                'redirect_uri' => $redirect_uri,
            ]
        )
    );
    $res = $req->send();
    if ($res->getHeader('content-type') != 'application/x-www-form-urlencoded') {
        logError('Wrong content type in auth verification response');
        exit(2);
    }
    parse_str($res->getBody(), $verifiedParams);
    if (!isset($verifiedParams['me'])
        || $verifiedParams['me'] !== $authParams['me']
    ) {
        logError('Non-matching "me" values');
        exit(2);
    }

    $userUrl = $verifiedParams['me'];


    //fetch permanent access token
    $req = new HTTP_Request2($endpoints->token, 'POST');
    $req->setHeader('Content-Type: application/x-www-form-urlencoded');
    $req->setBody(
        http_build_query(
            [
                'me'           => $userUrl,
                'code'         => $authParams['code'],
                'redirect_uri' => $redirect_uri,
                'client_id'    => $client_id,
                'state'        => $state,
            ]
        )
    );
    $res = $req->send();
    if ($res->getHeader('content-type') != 'application/x-www-form-urlencoded') {
        logError('Wrong content type in auth verification response');
        exit(2);
    }
    parse_str($res->getBody(), $tokenParams);
    if (!isset($tokenParams['access_token'])) {
        logError('"access_token" missing');
        exit(2);
    }

    $accessToken = $tokenParams['access_token'];

    return [$accessToken, $userUrl];
}

function startHttpServer()
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
    $server = stream_socket_server(
        'tcp://127.0.0.1:12345', $errno, $errstr
    );
    if (!$server) {
        //TODO: log
        return false;
    }

    do {
        $sock = stream_socket_accept($server);
        if (!$sock) {
            //TODO: log
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
            if (preg_match('#^Content-Length:\s*([[:digit:]]+)\s*$#i', $line, $matches)) {
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
    } while(true);
}

class Config_Endpoints
{
    public $micropub;
    public $media;
    public $token;
    public $authorization;
}

function discoverEndpoints($url)
{
    $cfg = new Config_Endpoints();

    //TODO: discovery via link headers

    $sx = simplexml_load_file($url);
    $sx->registerXPathNamespace('h', 'http://www.w3.org/1999/xhtml');

    $auths = $sx->xpath(
        '/h:html/h:head/h:link[@rel="authorization_endpoint" and @href]'
    );
    if (!count($auths)) {
        logError('No authorization endpoint found');
        exit(1);
    }
    $cfg->authorization = (string) $auths[0]['href'];

    $tokens = $sx->xpath(
        '/h:html/h:head/h:link[@rel="token_endpoint" and @href]'
    );
    if (!count($tokens)) {
        logError('No token endpoint found');
        exit(1);
    }
    $cfg->token = (string) $tokens[0]['href'];

    $mps = $sx->xpath(
        '/h:html/h:head/h:link[@rel="micropub" and @href]'
    );
    if (!count($mps)) {
        logError('No micropub endpoint found');
        exit(1);
    }
    $cfg->micropub = (string) $mps[0]['href'];

    return $cfg;
}

function logError($msg)
{
    file_put_contents('php://stderr', $msg . "\n", FILE_APPEND);
}
?>
