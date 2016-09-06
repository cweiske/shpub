#!/usr/bin/env php
<?php
namespace shpub;

if (file_exists(__DIR__ . '/../src/shpub/Autoloader.php')) {
    include_once __DIR__ . '/../src/shpub/Autoloader.php';
    Autoloader::register();
}
$cli = new Cli();
$cli->run();
?>
