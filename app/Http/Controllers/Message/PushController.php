<?php
namespace App\Http\Controllers\Message;

use App\Http\Controllers\Controller;
use App\Services\Message\PushService;
use Illuminate\Http\Request;

class PushController extends  Controller
{
    protected $pushService;

    public function __construct(PushService $pushService)
    {
        $this->pushService = $pushService;
    }

    /**
     * 绑定客户端推送ID
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bind(Request $request)
    {
        $cid = $request->input('cid');
        $result = $this->pushService->bind($cid, $this->getUserId());
        if ($result) {
            return success();
        } else {
            return out(1, '绑定推送客户端ID失败');
        }
    }
}
