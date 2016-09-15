<?php
namespace shpub;

class Command_Article extends Command_AbstractProps
{
    public static function opts(\Console_CommandLine $optParser)
    {
        $cmd = $optParser->addCommand('article');
        static::addOptHtml($cmd);
        static::optsGeneric($cmd);
        $cmd->addArgument(
            'title',
            [
                'optional'    => false,
                'multiple'    => false,
                'description' => 'Title/Name',
            ]
        );
        $cmd->addArgument(
            'text',
            [
                'optional'    => false,
                'multiple'    => false,
                'description' => 'Text content',
            ]
        );
    }

    public function run(\Console_CommandLine_Result $cmdRes)
    {
        $req = new Request($this->cfg->host, $this->cfg);
        $req->setType('entry');
        $req->addProperty('name', $cmdRes->args['title']);
        $req->addContent($cmdRes->args['text'], $cmdRes->options['html']);
        $this->handleGenericOptions($cmdRes, $req);

        $res = $req->send();
        $postUrl = $res->getHeader('Location');
        Log::info('Article created at server');
        Log::msg($postUrl);
    }
}
?>
