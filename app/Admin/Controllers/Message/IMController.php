<?php
namespace App\Admin\Controllers\Message;

use App\Admin\Controllers\ScController;
use App\Services\Message\IMService;
use Illuminate\Http\Request;

class IMController extends ScController
{
    protected $IMService;

    public function __construct(IMService $IMService)
    {
        $this->IMService = $IMService;
    }

    /**
     * 获取登录信息
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\BusinessException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getLoginParams(Request $request)
    {
        $id = $request->input('admin.user_id');
        $res = $this->IMService->getLoginParams($id);
        return success($res);
    }

}
