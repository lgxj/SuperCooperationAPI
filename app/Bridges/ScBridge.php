<?php


namespace App\Bridges;


class ScBridge
{

    protected $service;

    public function __call($name, $arguments)
    {
        if( method_exists($this->service,$name)){
           return call_user_func_array([$this->service, $name], $arguments);
        }
    }
}
