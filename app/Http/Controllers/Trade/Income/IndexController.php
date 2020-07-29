<?php


namespace App\Http\Controllers\Trade\Income;


use App\Consts\Trade\OrderConst;
use App\Http\Controllers\Controller;
use App\Services\Trade\Fund\AccountService;
use App\Services\Trade\Fund\InoutLogService;
use App\Services\Trade\Order\Helper\HelperService;
use Illuminate\Http\Request;

class IndexController extends Controller
{

    protected $inoutLogService = null;

    public function __construct(InoutLogService $inoutLogService)
    {
        $this->inoutLogService = $inoutLogService;
    }

    public function options(Request $request){
        $condition['time_period'] = get_time_period(6);
        return success($condition);
    }

    public function list(Request $request){
        $time_period = $request->get('time_period');
        $page = $request->get('page',1);
        $list = $this->inoutLogService->getListByMonth($this->getUserId(),$time_period,$page);
        return success($list);
    }

    public function getAccount(Request $request){
        $accountService = $this->getAccountService();
        $account = $accountService->getAccountByUserId($this->getUserId());
        $account['today_task_num'] = $this->getHelperService()->countTodayTask($this->getUserId(),[OrderConst::EMPLOYER_STATE_COMPLETE]);
        $account['today_income'] = display_price($this->inoutLogService->getTodayIncome($this->getUserId()));
        $account['available_balance'] = display_price($account['available_balance']);
        $account['freeze'] = display_price($account['freeze']);

        return success($account);
    }

    protected function getAccountService(){
        return new AccountService();
    }

    protected function getHelperService(){
        return new HelperService();
    }
}
