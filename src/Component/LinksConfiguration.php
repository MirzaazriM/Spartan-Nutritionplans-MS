<?php
/**
 * Created by PhpStorm.
 * User: mirza
 * Date: 7/16/18
 * Time: 11:33 AM
 */

namespace Component;


class LinksConfiguration
{

    private $config = 'LOCAL';
    private $localTagsUrl = 'http://spartan-tags:8888';
    private $localRecepiesUrl = 'http://spartan-recepies:8888';
    private $onlineTagsUrl = '12.456.43.54';
    private $onlineRecepiesUrl = '467.21.980.046';

    public function __construct()
    {
    }

    public function getUrls():array {

        if($this->config == 'LOCAL'){
            return [
                $this->localTagsUrl,
                $this->localRecepiesUrl
            ];
        }else {
            return [
                $this->onlineTagsUrl,
                $this->onlineRecepiesUrl
            ];
        }
    }
}