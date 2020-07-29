<?php


namespace App\Bridges\Pool;


use App\Bridges\ScBridge;
use App\Services\Pool\YunTuService;

class YunTuBridge extends ScBridge
{
    public function __construct(YunTuService $yunTuService)
    {
        $this->service = $yunTuService;
    }
}
