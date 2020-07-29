<?php


namespace App\Services\Trade\Pay\Gateway\ScWeixin;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Yansongda\Pay\Events;
use Yansongda\Pay\Exceptions\GatewayException;
use Yansongda\Pay\Exceptions\InvalidArgumentException;
use Yansongda\Pay\Exceptions\InvalidSignException;
use Yansongda\Pay\Gateways\Wechat;
use Yansongda\Pay\Gateways\Wechat\Support;
use Yansongda\Supports\Collection;

class TransferBankGateway extends Wechat\Gateway
{

    /**
     * Pay an order.
     *
     * @param string $endpoint
     * @param array $payload
     *
     * @return Collection|Response
     * @throws GatewayException
     * @throws InvalidArgumentException
     * @throws InvalidSignException
     * @author yansongda <me@yansongda.cn>
     */
    public function pay($endpoint, array $payload)
    {
        if ($this->mode === Wechat::MODE_SERVICE) {
            unset($payload['sub_mch_id'], $payload['sub_appid']);
        }

        $type = Support::getTypeName($payload['type'] ?? '');

        $payload['mch_appid'] = Support::getInstance()->getConfig($type, '');
        $payload['mchid'] = $payload['mch_id'];

        if (php_sapi_name() !== 'cli' && !isset($payload['spbill_create_ip'])) {
            $payload['spbill_create_ip'] = Request::createFromGlobals()->server->get('SERVER_ADDR');
        }

        unset($payload['appid'], $payload['mch_id'], $payload['trade_type'],
            $payload['notify_url'], $payload['type']);

        $payload['sign'] = Support::generateSign($payload);

        Events::dispatch(new Events\PayStarted('Wechat', 'Transfer', $endpoint, $payload));

        return Support::requestApi(
            'mmpaysptrans/pay_bank',
            $payload,
            true
        );
    }

    /**
     * Get trade type config.
     *
     * @return string
     * @author yansongda <me@yansongda.cn>
     *
     */protected function getTradeType()
     {
        return '';
     }
}
