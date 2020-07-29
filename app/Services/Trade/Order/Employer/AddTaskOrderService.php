<?php


namespace App\Services\Trade\Order\Employer;


use App\Consts\DBConnection;
use App\Consts\ErrorCode\TaskOrderErrorCode;
use App\Consts\Trade\OrderConst;
use App\Consts\Trade\PayConst;
use App\Consts\UserConst;
use App\Events\TaskOrder\TaskAddEvent;
use App\Exceptions\BusinessException;
use App\Models\Trade\Entity\TaskOrderEntity;
use App\Models\Trade\Order\TaskOrder;
use App\Models\Trade\Order\Text;
use App\Services\Trade\Order\BaseTaskOrderService;
use App\Services\Trade\Traits\OrderEntityCheckTrait;
use App\Utils\UniqueNo;
use Illuminate\Support\Facades\DB;

/**
 * 雇主发布任务
 *
 * Class AddTaskOrderService
 * @package App\Services\Trade\Order\Employer
 */
class AddTaskOrderService extends BaseTaskOrderService
{
    use OrderEntityCheckTrait;

    /**
     * @param TaskOrderEntity $taskOrderEntity
     * @return array
     * @throws BusinessException
     */
    public function publish(TaskOrderEntity $taskOrderEntity){

        if(empty($taskOrderEntity->orderNo)){
            $taskOrderEntity->orderNo = UniqueNo::buildTaskOrderNo($taskOrderEntity->userId,$taskOrderEntity->orderType);
        }
        $this->taskOrderPriceToDb($taskOrderEntity);
        $this->checkGeneral($taskOrderEntity);
        $this->checkOrderText($taskOrderEntity);
        $this->checkTime($taskOrderEntity);
        $this->checkPrice($taskOrderEntity);
        $arrayEntity = $this->orderEntityToArray($taskOrderEntity);
        $detailOrderService = $this->getDetailService();
        $connection = DBConnection::getTradeConnection();
        try {
            $connection->beginTransaction();
            $taskOrder = new TaskOrder();
            $fields = $taskOrder->getTableColumns();
            foreach ($fields as $field) {
                if ($field == $taskOrder->getKeyName()) {
                    continue;
                }
                if (isset($arrayEntity[$field])) {
                    $taskOrder->$field = $arrayEntity[$field];
                }
            }
            $taskOrder->pay_price = $arrayEntity['pay_price'];
            $taskOrder->order_state = OrderConst::EMPLOYER_STATE_UN_START;
            $taskOrder->pay_state = PayConst::STATE_UN_PAY;
            $op = $taskOrder->save();
            if(!$op){
                return [];
            }
            $this->addOrderText($taskOrderEntity);
            $this->addOrderAddressList($taskOrderEntity);
            $this->batchAddOrderService($taskOrderEntity,OrderConst::PRICE_OP_STATE_UN_HANDLE,OrderConst::PRICE_CHANGE_ORDER_PAY);
            $connection->commit();
            $latestPriceChange = $detailOrderService->getLatestChangePayPrice($taskOrderEntity->orderNo,OrderConst::PRICE_CHANGE_ORDER_PAY,true);
            $taskOrderArray = $taskOrder->toArray();
            $taskOrderArray['price_list'] = $latestPriceChange;
            event(new TaskAddEvent($taskOrder['order_no'],$taskOrder['order_type']));
            return  $taskOrderArray;

        }catch (\Exception $e) {
            $connection->rollBack();
            \Log::error("任务发布失败 message:{$e->getMessage()}");
            throw new BusinessException($e->getMessage(),TaskOrderErrorCode::SAVE_FAILED);
        }
    }

    /**
     * @param TaskOrderEntity $taskOrderEntity
     * @return array
     * @throws BusinessException
     */
    public function addOrderText(TaskOrderEntity $taskOrderEntity){
        $this->checkOrderText($taskOrderEntity);
        $orderText = new Text();
        $orderText->user_id = $taskOrderEntity->userId;
        $orderText->order_no = $taskOrderEntity->orderNo;
        $orderText->voice_text = $taskOrderEntity->voiceText;
        $orderText->voice_url = $taskOrderEntity->voiceUrl;
        $orderText->memo = $taskOrderEntity->memo;
        if($taskOrderEntity->attachmentList){
            $orderText->attachment_url_list = json_encode($taskOrderEntity->attachmentList);
        }
        $orderText->save();
        return $orderText->toArray();
    }

}
