<?php


namespace App\Admin\Controllers\Funds;


use App\Admin\Controllers\ScController;
use App\Bridges\Trade\Admin\WithDrawManagerBridge;
use App\Bridges\Trade\InoutLogBridge;
use App\Bridges\Trade\PayBridge;
use App\Bridges\Trade\RefundBridge;
use App\Services\Trade\Fund\Admin\WithDrawManagerService;
use App\Services\Trade\Fund\InoutLogService;
use App\Services\Trade\Pay\PayService;
use App\Services\Trade\Refund\RefundService;
use Illuminate\Http\Request;

class PayManagerController extends ScController
{
    /**
     * @var PayService
     */
    protected $managerService;

    public function __construct(PayBridge $service)
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

    public function refundSearch(Request $request){
        $filter = $request->input('filter');
        $filter = json_decode($filter, true);
        $pageSize = $request->input('limit');
        $result = $this->getRefundBridge()->search($filter,$pageSize);
        return success(formatPaginate($result));
    }

    public function inoutLogSearch(Request $request){
        $filter = $request->input('filter');
        $filter = json_decode($filter, true);
        $pageSize = $request->input('limit');
        $result = $this->geInoutLogBridge()->search($filter,$pageSize);
        return success(formatPaginate($result));
    }

    /**
     * @return RefundService
     */
    protected function getRefundBridge(){
        return new RefundBridge(new RefundService());
    }

    /**
     * @return InoutLogService
     */
    protected function geInoutLogBridge(){
        return new InoutLogBridge(new InoutLogService());
    }
}
