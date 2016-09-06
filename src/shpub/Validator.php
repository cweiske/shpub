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
            Log::err('Invalid URL: No host in ' . $helpName);
            return false;
        }

        return $url;
    }
}
?>
