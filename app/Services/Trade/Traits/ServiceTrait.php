<?php


namespace App\Services\Trade\Traits;


use App\Bridges\Pool\GlobalConfigBridge;
use App\Bridges\User\UserBridge;
use App\Services\Pool\ConfigService;
use App\Services\Trade\Fee\FeeTaskService;
use App\Services\Trade\Fund\AccountService;
use App\Services\Trade\Fund\CompensateService;
use App\Services\Trade\Fund\InoutLogService;
use App\Services\Trade\Order\BaseTaskOrderService;
use App\Services\Trade\Order\Employer\DetailTaskOrderService;
use App\Services\Trade\Order\Helper\HelperService;
use App\Services\Trade\Order\Helper\SearchService;
use App\Services\Trade\Order\State\TaskOrderStateService;
use App\Services\Trade\Pay\Gateway\BalancePayment;
use App\Services\Trade\Pay\PayService;
use App\Services\Trade\Pay\PayTaskOrderService;
use App\Services\Trade\Refund\RefundService;
use App\Services\Trade\Refund\RefundTaskOrderService;
use App\Services\User\UserService;

trait ServiceTrait
{
    protected function getStateService(){
        return new TaskOrderStateService();
    }

    protected function getPayService(){
        return new PayService();
    }

    protected function getInoutLogService(){
        return new InoutLogService();
    }

    protected function getBalancePayment(){
        return new BalancePayment();
    }

    protected function getDetailService(){
        return new DetailTaskOrderService();
    }

    protected function getFeeTaskService(){
        return new FeeTaskService();
    }

    protected function getCompensateService(){
        return new CompensateService();
    }

    protected function getAccountService(){
        return new AccountService();
    }

    protected function getPayTaskOrderService(){
        return new PayTaskOrderService();
    }

    protected function getBaseTaskOrderService(){
        return new BaseTaskOrderService();
    }

    protected function getRefundTaskOrderService(){
        return new RefundTaskOrderService();
    }

    protected function getRefundService(){
        return new RefundService();
    }
    protected function getOrderSearchService(){
        return new SearchService();
    }

    protected function getHelperService(){
        return new HelperService();
    }
    /**
     * @return UserService
     */
    protected function getUserService(){
        return new UserBridge(new UserService());
    }

    /**
     * @return ConfigService
     */
    protected function getGlobalConfigBridge(){
        return new GlobalConfigBridge(new ConfigService());
    }


}
