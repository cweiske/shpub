<?php
namespace shpub;

class Validator
{
    public static function url($url, $helpName)
    {
        $parts = parse_url($url);
        if (!isset($parts['scheme'])) {
            $url = 'http://' . $url;
        } else if ($parts['scheme'] != 'http' && $parts['scheme'] != 'https') {
            Log::err(
                'Invalid URL scheme in ' . $helpName . ': ' . $parts['scheme']
            );
            return false;
        }

        if (!isset($parts['host'])) {
            if (count($parts) == 1 && isset($parts['path'])) {
                //parse_url('example.org') puts 'example.org' in the path
                // but this is common, so we fix it.
                $url = 'http://' . $parts['path'];
            } else {
                Log::err('Invalid URL: No host in ' . $helpName);
                return false;
            }
        }

        return $url;
    }

    public static function rsvp($answer)
    {
        $allowed = ['yes', 'no', 'maybe'];
        if (false === array_search($answer, $allowed)) {
            Log::err(
                'Invalid RSVP answer given; allowed are: '
                . implode(', ', $allowed)
            );
            return false;
        }
        return $answer;
    }
}
?>
