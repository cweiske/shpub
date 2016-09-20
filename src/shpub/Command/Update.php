<?php
namespace shpub;

class Command_Update extends Command_AbstractProps
{
    public static function opts(\Console_CommandLine $optParser)
    {
        $cmd = $optParser->addCommand('update');
        $cmd->description = 'Modify an existing post';
        $cmd->addOption(
            'add',
            array(
                'short_name'  => '-a',
                'long_name'   => '--add',
                'description' => 'Property to add',
                'help_name'   => 'PROP=VAL',
                'action'      => 'StoreArray',
                'default'     => [],
            )
        );
        $cmd->addOption(
            'replace',
            array(
                'short_name'  => '-r',
                'long_name'   => '--replace',
                'description' => 'Property to remove',
                'help_name'   => 'PROP=VAL',
                'action'      => 'StoreArray',
                'default'     => [],
            )
        );
        $cmd->addOption(
            'delete',
            array(
                'short_name'  => '-d',
                'long_name'   => '--delete',
                'description' => 'Property to delete',
                'help_name'   => 'PROP=VAL',
                'action'      => 'StoreArray',
                'default'     => [],
            )
        );
        $cmd->addArgument(
            'url',
            [
                'optional'    => false,
                'multiple'    => false,
                'description' => 'Post URL',
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

        $json = [
            'action' => 'update',
            'url'    => $url,
        ];

        $parts = [
            'add'     => [],
            'delete'  => [],
            'replace' => [],
        ];
        foreach (['add', 'delete', 'replace'] as $part) {
            if (!count($cmdRes->options[$part])) {
                continue;
            }
            foreach ($cmdRes->options[$part] as $kvpair) {
                list($prop, $val) = explode('=', $kvpair, 2);
                if (!isset($parts[$part][$prop])) {
                    $parts[$part][$prop] = [];
                }
                $parts[$part][$prop][] = $val;
            }
        }
        foreach ($parts as $part => $changes) {
            if (count($changes)) {
                $json[$part] = $changes;
            }
        }

        $req->req->setHeader('Content-Type: application/json');
        $res = $req->send(json_encode($json));
        $newPostUrl = $res->getHeader('Location');
        Log::info('Post updated at server');
        if ($newPostUrl) {
            Log::info('Post has a new URL:');
            Log::msg($newPostUrl);
        }
    }
}
?>
