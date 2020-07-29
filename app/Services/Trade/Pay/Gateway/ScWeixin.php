<?php


namespace App\Services\Trade\Pay\Gateway;


use Symfony\Component\HttpFoundation\Response;
use Yansongda\Pay\Events;
use Yansongda\Pay\Exceptions\InvalidGatewayException;
use Yansongda\Pay\Gateways\Wechat;
use Yansongda\Supports\Collection;
use Yansongda\Supports\Str;

/**
 * Class ScWeixin
 * @package App\Services\Trade\Pay\Gateway
 */
class ScWeixin extends Wechat
{
    /**
     * transfer bank
     *
     * @author yansongda <me@yansongda.cn>
     *
     * @param array  $params
     *
     * @throws InvalidGatewayException
     *
     * @return Response|Collection
     */
    public function transferBank(array  $params){
        $gateway = 'transferBank';
        Events::dispatch(new Events\PayStarting('Wechat', $gateway, $params));

        $this->payload = array_merge($this->payload, $params);

        $gateway = get_class($this).'\\'.Str::studly($gateway).'Gateway';

        if (class_exists($gateway)) {
            return $this->makePay($gateway);
        }

    }
}
