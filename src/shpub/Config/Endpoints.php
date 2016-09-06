<?php
namespace shpub;

class Config_Endpoints
{
    /**
     * Micropub endpoint URL
     *
     * @var string
     */
    public $micropub;

    /**
     * Micropub media endpoint URL
     *
     * @var string
     */
    public $media;

    /**
     * Access token endpoint URL
     *
     * @var string
     */
    public $token;

    /**
     * Authorization endpoint URL
     *
     * @var string
     */
    public $authorization;

    public function incomplete()
    {
        return $this->authorization === null
            || $this->token === null
            || $this->micropub === null;
    }
}
?>
