<?php
namespace shpub;

class Command_Upload
{
    public function __construct(Config $cfg)
    {
        $this->cfg = $cfg;
    }

    public static function opts(\Console_CommandLine $optParser)
    {
        $cmd = $optParser->addCommand('upload');
        $cmd->description = 'Directly upload files to the media endpoint';
        $cmd->addArgument(
            'files',
            [
                'optional'    => false,
                'multiple'    => true,
                'description' => 'File paths',
            ]
        );
    }

    public function run(\Console_CommandLine_Result $cmdRes)
    {
        if ($this->cfg->host->endpoints->media == '') {
            Log::err('Host as no media endpoint');
            exit(20);
        }

        $req = new Request($this->cfg->host, $this->cfg);

        foreach ($cmdRes->args['files'] as $filePath) {
            if (!file_exists($filePath)) {
                Log::err('File does not exist: ' . $filePath);
                exit(20);
            }

            $url = $req->uploadToMediaEndpoint($filePath);
            Log::info('Uploaded file ' . $filePath);
            Log::msg($url);
        }
    }
}
?>
