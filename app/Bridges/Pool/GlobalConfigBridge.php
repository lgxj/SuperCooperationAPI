<?php


namespace App\Bridges\Pool;


use App\Bridges\ScBridge;
use App\Services\Pool\ConfigService;

class GlobalConfigBridge extends ScBridge
{
    public function __construct(ConfigService $configService)
    {
        $this->service = $configService;
    }

}
