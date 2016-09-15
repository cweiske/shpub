<?php
namespace shpub;

class Command_Reply extends Command_AbstractProps
{
    public static function opts(\Console_CommandLine $optParser)
    {
        $cmd = $optParser->addCommand('reply');
        static::addOptHtml($cmd);
        static::optsGeneric($cmd);
        $cmd->addArgument(
            'url',
            [
                'optional'    => false,
                'description' => 'URL that is replied to',
            ]
        );
        $cmd->addArgument(
            'text',
            [
                'optional'    => false,
                'multiple'    => false,
                'description' => 'Reply text',
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
        $req->addProperty('in-reply-to', $url);
        $req->addContent($cmdRes->args['text'], $cmdRes->options['html']);

        $this->handleGenericOptions($cmdRes, $req);
        $res = $req->send();

        $postUrl = $res->getHeader('Location');
        Log::info('Reply created at server');
        Log::msg($postUrl);
    }
}
?>
