#!/usr/bin/env php
<?php
/**
 * Phar stub file for shpub. Handles startup of the .phar file.
 *
 * PHP version 5
 *
 * @author    Christian Weiske <cweiske@cweiske.de>
 * @copyright 2016 Christian Weiske
 * @license   http://www.gnu.org/licenses/agpl.html GNU AGPL v3
 * @link      http://cweiske.de/shpub.htm
 */
if (!in_array('phar', stream_get_wrappers()) || !class_exists('Phar', false)) {
    echo "Phar extension not avaiable\n";
    exit(255);
}

Phar::mapPhar('shpub.phar');
set_include_path(
    'phar://' . __FILE__
    . PATH_SEPARATOR . 'phar://' . __FILE__ . '/lib/'
);

require 'phar://' . __FILE__ . '/bin/phar-shpub.php';
__HALT_COMPILER();
?>
