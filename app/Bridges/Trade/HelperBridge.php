<?php


namespace App\Bridges\Trade;


use App\Bridges\ScBridge;
use App\Services\Trade\Order\Helper\HelperService;

class HelperBridge extends ScBridge
{
    public function __construct(HelperService $helperService)
    {
        $this->service = $helperService;
    }

}
