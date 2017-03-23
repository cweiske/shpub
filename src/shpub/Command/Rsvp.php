<?php
namespace shpub;

/**
 * Repondez s'il vous plait - an answer to an invitation
 *
 * @author  Christian Weiske <cweiske@cweiske.de>
 * @license http://www.gnu.org/licenses/agpl.html GNU AGPL v3
 * @link    http://cweiske.de/shpub.htm
 */
class Command_Rsvp extends Command_AbstractProps
{
    public static function opts(\Console_CommandLine $optParser)
    {
        $cmd = $optParser->addCommand('rsvp');
        $cmd->description = 'Create a reservation';
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
            'rsvp',
            [
                'optional'    => false,
                'multiple'    => false,
                'description' => 'Answer: yes, no, maybe',
            ]
        );
        $cmd->addArgument(
            'text',
            [
                'optional'    => true,
                'multiple'    => false,
                'description' => 'Answer text',
            ]
        );
    }

    public function run(\Console_CommandLine_Result $cmdRes)
    {
        $url = Validator::url($cmdRes->args['url'], 'url');
        if ($url === false) {
            exit(10);
        }
        $rsvp = Validator::rsvp($cmdRes->args['rsvp']);
        if ($rsvp === false) {
            exit(10);
        }

        $req = new Request($this->cfg->host, $this->cfg);
        $req->setType('entry');
        $req->addProperty('in-reply-to', $url);
        $req->addProperty('rsvp', $rsvp);
        if ($cmdRes->args['text']) {
            $req->addContent($cmdRes->args['text'], $cmdRes->options['html']);
        }
        $this->handleGenericOptions($cmdRes, $req);

        $res = $req->send();
        $postUrl = $res->getHeader('Location');
        Log::info('RSVP created at server');
        Log::msg($postUrl);
    }
}
?>
