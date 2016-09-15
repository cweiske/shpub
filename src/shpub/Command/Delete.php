<?php
namespace shpub;

class Command_Delete extends Command_AbstractProps
{
    public static function opts(\Console_CommandLine $optParser)
    {
        $cmd = $optParser->addCommand('delete');
        static::addOptJson($cmd);
        $cmd->addArgument(
            'url',
            [
                'optional'    => false,
                'description' => 'URL to remove',
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
        $this->handleOptJson($cmdRes, $req);
        $req->setAction('delete');
        $req->setUrl($url);

        $res = $req->send();
        Log::info('Post deleted from server');
    }
}
?>
