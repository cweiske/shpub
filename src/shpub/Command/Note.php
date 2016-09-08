<?php
namespace shpub;

class Command_Note
{
    /**
     * @var Config
     */
    protected $cfg;

    public function __construct($cfg)
    {
        $this->cfg = $cfg;
    }

    public static function opts(\Console_CommandLine $optParser)
    {
        $cmd = $optParser->addCommand('note');
        $cmd->addOption(
            'files',
            array(
                'short_name'  => '-f',
                'long_name'   => '--files',
                'description' => 'Files to upload',
                'help_name'   => 'PATH',
                'action'      => 'StoreArray',
                'default'     => [],
            )
        );
        $cmd->addArgument(
            'text',
            [
                'optional'    => false,
                'multiple'    => false,
                'description' => 'Post text',
            ]
        );
    }

    public function run($command)
    {
        $req = new Request($this->cfg->host, $this->cfg);
        $req->req->addPostParameter('h', 'entry');
        $req->req->addPostParameter('content', $command->args['text']);

        $files = $command->options['files'];
        $fileList = [
            'audio' => [],
            'photo' => [],
            'video' => [],
        ];
        foreach ($files as $filePath) {
            if (!file_exists($filePath)) {
                Log::err('File does not exist: ' . $filePath);
                exit(20);
            }
            $type = 'photo';
            $fileList[$type][] = $filePath;
        }
        foreach ($fileList as $type => $filePaths) {
            if (count($filePaths) == 1) {
                $req->addUpload($type, reset($filePaths));
            } else if (count($filePaths) > 0) {
                $req->addUpload($type, $filePaths);
            }
        }

        $res = $req->send();
        $postUrl = $res->getHeader('Location');
        echo "Post created at server\n";
        echo $postUrl . "\n";
    }
}
?>
