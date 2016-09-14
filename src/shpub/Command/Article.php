<?php
namespace shpub;

class Command_Article extends Command_AbstractProps
{
    public static function opts(\Console_CommandLine $optParser)
    {
        $cmd = $optParser->addCommand('article');
        $cmd->addOption(
            'html',
            array(
                'short_name'  => '-h',
                'long_name'   => '--html',
                'description' => 'Text content is HTML',
                'action'      => 'StoreTrue',
                'default'     => false,
            )
        );
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
        $req->req->addPostParameter('h', 'entry');
        $req->req->addPostParameter('name', $cmdRes->args['title']);
        if ($cmdRes->options['html']) {
            $req->req->addPostParameter(
                'content[html]', $cmdRes->args['text']
            );
        } else {
            $req->req->addPostParameter('content', $cmdRes->args['text']);
        }
        $this->handleGenericOptions($cmdRes, $req);

        $res = $req->send();
        $postUrl = $res->getHeader('Location');
        Log::info('Article created at server');
        Log::msg($postUrl);
    }
}
?>
