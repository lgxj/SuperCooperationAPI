<?php


namespace App\Services\Trade\Pay\Gateway\ScAlipay;


use Symfony\Component\HttpFoundation\Response;
use Yansongda\Pay\Contracts\GatewayInterface;
use Yansongda\Pay\Exceptions\InvalidArgumentException;
use Yansongda\Supports\Collection;

class TransferBankGateway implements GatewayInterface
{

    /**
     * Pay an order.
     *
     * @param string $endpoint
     * @param array $payload
     *
     * @return Collection|Response
     * @throws InvalidArgumentException
     * @author yansongda <me@yansongda.cn>
     *
     */public function pay($endpoint, array $payload)
     {
         throw new InvalidArgumentException('Not Support transfer bank In Pay');

     }
}
