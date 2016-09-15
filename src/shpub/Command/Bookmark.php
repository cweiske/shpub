<?php
namespace shpub;

class Command_Bookmark extends Command_AbstractProps
{
    public static function opts(\Console_CommandLine $optParser)
    {
        $cmd = $optParser->addCommand('bookmark');
        static::optsGeneric($cmd);
        $cmd->addArgument(
            'url',
            [
                'optional'    => false,
                'description' => 'URL to bookmark',
            ]
        );
        $cmd->addArgument(
            'text',
            [
                'optional'    => true,
                'multiple'    => false,
                'description' => 'Bookmark text',
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
        $req->setType('entry');
        $req->addProperty('bookmark-of', $url);

        if ($cmdRes->args['text']) {
            $req->addProperty('content', $cmdRes->args['text']);
        }


        $this->handleGenericOptions($cmdRes, $req);
        $res = $req->send();

        $postUrl = $res->getHeader('Location');
        if ($postUrl === null) {
            Log::err('Error: Server sent no "Location" header and said:');
            Log::err($res->getBody());
            exit(20);
        } else {
            Log::info('Bookmark created at server');
            Log::msg($postUrl);
        }
    }
}
?>
