<?php
namespace shpub;

class Command_Note extends Command_AbstractProps
{
    public static function opts(\Console_CommandLine $optParser)
    {
        $cmd = $optParser->addCommand('note');
        $cmd->description = 'Create a note';
        static::addOptHtml($cmd);
        static::optsGeneric($cmd);
        $cmd->addArgument(
            'text',
            [
                'optional'    => false,
                'multiple'    => false,
                'description' => 'Post text',
            ]
        );
    }

    public function run(\Console_CommandLine_Result $cmdRes)
    {
        $req = new Request($this->cfg->host, $this->cfg);
        $req->setType('entry');
        $req->addContent($cmdRes->args['text'], $cmdRes->options['html']);
        $this->handleGenericOptions($cmdRes, $req);

        $res = $req->send();
        $postUrl = $res->getHeader('Location');
        Log::info('Post created at server');
        Log::msg($postUrl);
    }
}
?>
