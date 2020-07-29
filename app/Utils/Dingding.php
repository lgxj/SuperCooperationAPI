<?php

namespace App\Utils;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

/**
 * 钉钉报警群业务通知
 *
 * Class Dingding
 * @package App\Utils
 */
class Dingding
{

    const ROBOT_URI = "https://oapi.dingtalk.com/robot/send?access_token=%s";

    /**
     * @param Exception $exception
     */
    public static function robot(Exception $exception)
    {
        $client = new Client();
        $uri = sprintf(self::ROBOT_URI, env('DINGDING_TOKEN'));
        $code = $exception->getCode();
        $json = [
            'msgtype' => 'markdown',
            'markdown' => [
                'title' => 'exception',
                'text' => "异常报警 : {$exception->getMessage()}\n" . "> code:{$code}  
                line:{$exception->getLine()}  
                file:{$exception->getFile()}
                ",
            ],
            'at' => [
                'isAtAll' => true,
            ],
        ];
        $response = $client->request(
            'POST',
            $uri,
            [
                'connect_timeout' => 5,
                'timeout' => 10,
                'json' => $json,
                'verify'=>false
            ]
        );
        $body = json_decode($response->getBody(), true);
        if ($body['errcode'] != 0) {
            Log::error(
                "dingding robot api response error",
                ['request' => $json, 'reponse' => $body]
            );
        }
    }

}
