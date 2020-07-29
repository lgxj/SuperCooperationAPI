<?php


namespace App\Utils;

/**
 * Class Json
 * @package App\Utils
 */
class Json
{

    public static function encode(array $data){
        return json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    }

    public static function decode(string $data){
        return json_decode($data,true);
    }
}
