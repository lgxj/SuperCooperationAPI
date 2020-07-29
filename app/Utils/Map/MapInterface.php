<?php


namespace App\Utils\Map;
use GuzzleHttp\Client;


abstract class MapInterface
{

    protected $uri;
    protected $type;
    protected $secret;
    protected $sign;
    /**
     * @var Client
     */
    protected $client;

    public abstract function getAltitudeByAddress($address);
    public abstract function getAddressByAltitude($altitude);

    public function getType(){
        return $this->type;
    }

    public function getUri(){
        return $this->uri;
    }

    public function getSecret(){
        return $this->secret;
    }

    public function getSign(){
        return $this->sign;
    }

}
