<?php
namespace shpub;

class MyHttpRequest2 extends \HTTP_Request2
{
    public function getPostParams()
    {
        return $this->postParams;
    }
}