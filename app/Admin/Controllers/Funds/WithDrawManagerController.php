<?php


namespace App\Admin\Controllers\Funds;


use App\Admin\Controllers\ScController;
use App\Bridges\Trade\Admin\WithDrawManagerBridge;
use App\Bridges\Trade\WithDrawBridge;
use App\Consts\Trade\WithDrawConst;
use App\Exceptions\BusinessException;
use App\Services\Trade\Fund\Admin\WithDrawManagerService;
use App\Services\Trade\Fund\WithDrawService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yansongda\Pay\Exceptions\InvalidGatewayException;

class WithDrawManagerController extends ScController
{
    /**
     * @var WithDrawManagerService
     */
    protected $managerService;

    public function __construct(WithDrawManagerBridge $service)
    {
        $this->managerService = $service;
    }

    public function search(Request $request){
        $filter = $request->input('filter');
        $filter = json_decode($filter, true);
        $pageSize = $request->input('limit');
        $result = $this->managerService->search($filter,$pageSize);
        return success(formatPaginate($result));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws BusinessException
     * @throws InvalidGatewayException
     */
    public function retry(Request $request){
        $userId = $request->input('user_id',0);
        $withDrawNo = $request->input('withdraw_no','');
        $result = $this->getWithDrawBridge()->retry($userId,$withDrawNo);
        return success($result);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws BusinessException
     * @throws InvalidGatewayException
     */
    public function verify(Request $request){
        $userId = $request->input('user_id',0);
        $withDrawNo = $request->input('withdraw_no','');
        $withDrawStatus = $request->input('withdraw_status',WithDrawConst::STATUS_VERIFY);
        $result = $this->getWithDrawBridge()->verify($userId,$withDrawNo,$withDrawStatus);
        return success($result);
    }

    /**
     * @return WithDrawService
     */
    protected function getWithDrawBridge(){
        return new WithDrawBridge(new WithDrawService());
    }
}
