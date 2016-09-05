<?php
namespace shpub;

class Config_Host
{
    /**
     * (Base) server URL
     *
     * @var string
     */
    public $server;

    /**
     * User URL
     *
     * @var string
     */
    public $user;

    /**
     * Micropub access token
     *
     * @var string
     */
    public $token;

    /**
     * Host information cache
     *
     * @var Config_HostCache
     */
    public $cache;

    /**
     * If this host is the default one
     *
     * @var boolean
     */
    public $default;
}
?>
