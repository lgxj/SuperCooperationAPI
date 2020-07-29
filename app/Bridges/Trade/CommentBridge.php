<?php
namespace App\Bridges\Trade;

use App\Bridges\ScBridge;
use App\Services\Trade\Order\CommentService;

class CommentBridge extends ScBridge
{
    public function __construct(CommentService $commentService)
    {
        $this->service = $commentService;
    }

}
