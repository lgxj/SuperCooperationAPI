<?php


namespace App\Admin\Controllers\Funds;


use App\Admin\Controllers\ScController;
use App\Bridges\Trade\AccountBridge;
use App\Services\Trade\Fund\AccountService;
use Illuminate\Http\Request;

class AccountManagerController extends ScController
{
    /**
     * @var AccountService
     */
    protected $managerService;

    public function __construct(AccountBridge $service)
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

    public function get(Request $request){
        $userId = $request->get('user_id',$request->admin['user_id'] ?? 0);
        $result = $this->managerService->getAccountByUserId($userId);
        return success($result);
    }

    public function addBalance(Request $request){
        $userId = $request->post('user_id',0);
        $money = $request->post('money',0);
        //$adminId = $request->post('admin_id',0);
        $money = db_price($money);
        $result = $this->managerService->addBalance($userId,$money,$request->admin['user_id'] ?? 0);
        return success([$result]);
    }
}
