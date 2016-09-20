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
     * Host information
     *
     * @var Config_HostEndpoints
     */
    public $endpoints;

    /**
     * If this host is the default one
     *
     * @var boolean
     */
    public $default;

    public function __construct()
    {
        $this->endpoints = new Config_Endpoints();
    }

    public function loadEndpoints()
    {
        $this->endpoints = new Config_Endpoints();
        $this->endpoints->load($this->server);
        if ($this->endpoints->incomplete()) {
            $this->endpoints->discover($this->server);
            if ($this->token) {
                $this->endpoints->discoverMedia($this->token);
            }
            $this->endpoints->save($this->server);
        }
    }
}
?>
