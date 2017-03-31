<?php
namespace shpub;

/**
 * List syndication targets
 *
 * @author  Christian Weiske <cweiske@cweiske.de>
 * @license http://www.gnu.org/licenses/agpl.html GNU AGPL v3
 * @link    http://cweiske.de/shpub.htm
 */
class Command_Targets
{
    public function __construct(Config $cfg)
    {
        $this->cfg = $cfg;
    }

    public static function opts(\Console_CommandLine $optParser)
    {
        $cmd = $optParser->addCommand('targets');
        $cmd->description = 'List a server\'s syndication targets';
    }

    public function run(\Console_CommandLine_Result $cmdRes)
    {
        $req = new Request($this->cfg->host, $this->cfg);
        $req->req->setMethod('GET');
        $req->req->setHeader('Content-type');
        $req->req->getUrl()->setQueryVariable('q', 'syndicate-to');
        $res = $req->send();

        if ($res->getHeader('content-type') != 'application/json') {
            Log::err('response data are not of type application/json');
            exit(2);
        }

        $data = json_decode($res->getBody(), true);
        if (!isset($data['syndicate-to'])) {
            Log::err('"syndicate-to" property missing');
            exit(2);
        }

        foreach ($data['syndicate-to'] as $target) {
            Log::msg($target['name']);
            Log::msg(' ' . $target['uid']);
            if (isset($target['user'])) {
                Log::msg(' User: ' . $target['user']['name']);
            }
            if (isset($target['service'])) {
                Log::msg(' Service: ' . $target['service']['name']);
            }
        }
    }
}
?>
