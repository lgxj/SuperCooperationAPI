<?php


namespace App\Services\Trade\Traits;


use App\Models\Trade\Fee\FeeLog;
use App\Models\Trade\Fee\FeeRule;
use App\Models\Trade\Fee\RefundFeeLog;
use App\Models\Trade\Order\Address;
use App\Models\Trade\Order\Cancel;
use App\Models\Trade\Order\Compensate;
use App\Models\Trade\Order\PriceChange;
use App\Models\Trade\Order\ReceiverOrder;
use App\Models\Trade\Order\Search;
use App\Models\Trade\Order\Service;
use App\Models\Trade\Order\TaskOrder;
use App\Models\Trade\Pay\Pay;
use App\Models\Trade\Pay\PayMessage;
use App\Models\Trade\Pay\PayRefund;
use App\Models\User\DeliveryRecord;

trait ModelTrait
{
    protected function getTaskOrderModel(){
        return new TaskOrder();
    }

    protected function getReceiveModel(){
        return new ReceiverOrder();
    }

    protected function getPayModel(){
        return new Pay();
    }

    protected function getPriceChangeModel(){
        return new PriceChange();
    }

    protected function getPayMessageModel(){
        return new PayMessage();
    }

    protected function getCompensateModel(){
        return new Compensate();
    }

    protected function getAddressModel(){
        return new Address();
    }

    protected function getCancelModel(){
        return new Cancel();
    }

    protected function getFeeLogModel(){
        return new  FeeLog();
    }

    protected function getFeeRuleModel(){
        return new FeeRule();
    }

    protected function getServiceModel(){
        return new Service();
    }

    protected function getRefundModel(){
        return new PayRefund();
    }

    protected function getRefundFeeModel(){
        return new RefundFeeLog();
    }

    protected function getOrderSearchModel(){
        return new Search();
    }

    protected function getDeliveryRecordModel(){
        return new DeliveryRecord();
    }

}
