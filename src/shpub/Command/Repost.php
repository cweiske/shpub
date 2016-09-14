<?php
namespace shpub;

class Command_Repost extends Command_AbstractProps
{
    public static function opts(\Console_CommandLine $optParser)
    {
        $cmd = $optParser->addCommand('repost');
        static::optsGeneric($cmd);
        $cmd->addArgument(
            'url',
            [
                'optional'    => false,
                'description' => 'URL that shall be reposted',
            ]
        );
    }

    public function run(\Console_CommandLine_Result $cmdRes)
    {
        $url = Validator::url($cmdRes->args['url'], 'url');
        if ($url === false) {
            exit(10);
        }

        $req = new Request($this->cfg->host, $this->cfg);
        $req->req->addPostParameter('h', 'entry');
        $req->req->addPostParameter('repost-of', $url);

        $this->handleGenericOptions($cmdRes, $req);
        $res = $req->send();

        $postUrl = $res->getHeader('Location');
        if ($postUrl === null) {
            Log::err('Error: Server sent no "Location" header and said:');
            Log::err($res->getBody());
            exit(20);
        } else {
            Log::info('Repost created at server');
            Log::msg($postUrl);
        }
    }
}
?>
