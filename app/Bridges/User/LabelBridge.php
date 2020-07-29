<?php


namespace App\Bridges\User;


use App\Bridges\ScBridge;
use App\Services\User\LabelService;

class LabelBridge extends ScBridge
{
    public function __construct(LabelService $labelService)
    {
        $this->service = $labelService;
    }
}
