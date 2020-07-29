<?php


namespace App\Http\Controllers\Trade\Income;


use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Services\Trade\Fund\WithDrawService;
use Illuminate\Http\Request;
use Yansongda\Pay\Exceptions\InvalidGatewayException;

class WithDrawController extends Controller
{
    protected $withDrawService = null;

    public function __construct(WithDrawService $withDrawService)
    {
         $this->withDrawService = $withDrawService;
    }

    /**
     * @param Request $request
     * @return array
     * @throws BusinessException
     * @throws InvalidGatewayException
     */
    public function apply(Request $request){
        $money = $request->get('money',0);
        $type = $request->get('type',0);
        $transferType = $request->get('transferType','app');
        $id = $request->get('id',0);
        $result = $this->withDrawService->withDraw($this->getUserId(),$money,$type,$id,$transferType);
        return success($result);
    }

    public function list(Request $request){
        $time_period = $request->get('time_period');
        $page = $request->get('page',1);
        $list = $this->withDrawService->list($this->getUserId(),$time_period,$page);
        return success($list);
    }

    public function grantList(Request $request){
        $list = $this->withDrawService->grantList($this->getUserId());
        return success($list);
    }
}
