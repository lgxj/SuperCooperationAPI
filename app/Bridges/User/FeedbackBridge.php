<?php


namespace App\Bridges\User;


use App\Bridges\ScBridge;
use App\Services\User\feedbackService;

class FeedbackBridge extends ScBridge
{

    public function __construct(feedbackService $codeService)
    {
        $this->service = $codeService;
    }
}
