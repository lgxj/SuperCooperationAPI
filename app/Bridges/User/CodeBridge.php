<?php


namespace App\Bridges\User;


use App\Bridges\ScBridge;
use App\Services\User\CodeService;


class CodeBridge extends ScBridge
{
    public function __construct(CodeService $codeService)
    {
        $this->service = $codeService;
    }

}
