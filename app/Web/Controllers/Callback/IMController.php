<?php

namespace App\Web\Controllers\Callback;

use App\Bridges\Message\IMBridge;
use App\Services\Message\IMService;
use App\Web\Controllers\ScController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IMController extends ScController
{
    /**
     * @var IMService
     */
    protected $IMServiceBridge;

    public function __construct(IMBridge $IMServiceBridge)
    {
        $this->IMServiceBridge = $IMServiceBridge;
    }

    public function notify(Request $request)
    {
        $appId = $request->input('SdkAppid');
        if(empty($appId) && $appId != env('TIM_APPID') ){
            $return = [
                'ActionStatus' => 'ERROR',
                'ErrorCode' => 1,
                'ErrorInfo' => '处理失败'
            ];
        }else {
            $data = $request->input();
            $return = $this->IMServiceBridge->notify($data);
        }
        return \Response::json($return);
    }
}
