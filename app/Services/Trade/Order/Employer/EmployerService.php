<?php


namespace App\Services\Trade\Order\Employer;


use App\Bridges\Pool\YunTuBridge;
use App\Consts\Trade\OrderConst;
use App\Models\Trade\Order\Address;
use App\Models\Trade\Order\TaskOrder;
use App\Services\Pool\YunTuService;
use App\Services\Trade\Traits\ServiceTrait;
use App\Utils\Map\AMap;
use App\Utils\Map\TencentYunTu;
use App\Utils\Map\YunTu;
use App\Utils\Map\YunTuInterface;
use Illuminate\Support\Facades\Log;

/**
 * 任务通过异步事件同步地点云
 *
 * Class EmployerService
 * @package App\Services\Trade\Order\Employer
 */
class EmployerService
{
    use ServiceTrait;

    /**
     * 任务同步地点云
     *
     * @param $orderNo
     * @param bool $isSaveYunTu
     * @return int|mixed
     * @throws \App\Exceptions\BusinessException
     */
    public function saveEmployerYuTuAddress($orderNo, $isSaveYunTu = true){
        $yunTuBridge = $this->getYunTuBridge();
        $detailService = $this->getDetailService();
        $addressList = $detailService->getAddressByOrderNos([$orderNo]);
        $addressList = $addressList[$orderNo] ?? [];
        if(empty($addressList)) {
            Log::error("saveEmployerYuTuAddress order no address list");
            return 0;
        }
        $AMap = new AMap();
        $address =  $addressList[0] ?? [];
        $taskOrder = TaskOrder::getModel()->getByOrderNo($orderNo);
        $detailAddress = $this->getDetailAddress($address);
        if($address['lng'] <=0 || $address['lat'] <= 0){
            $altitude = $AMap->getAltitudeByAddress($detailAddress);
            if($altitude) {
                list($address['lng'], $address['lat']) = $altitude;
                $this->updateAddressAltitude($orderNo,$address['order_address_id'],$address['lng'],$address['lat']);
            }
        }
        if(count($addressList ) == 2){
            $address2 =  $addressList[1] ?? [];
            $detailAddress2 = $this->getDetailAddress($address2);
            if($address2['lng'] <=0 || $address2['lat'] <= 0){
                $altitude2 = $AMap->getAltitudeByAddress($detailAddress2);
                if($altitude2) {
                    list($address2['lng'], $address2['lat']) = $altitude2;
                    $this->updateAddressAltitude($orderNo,$address2['order_address_id'],$address2['lng'],$address2['lat']);
                }
            }
            if($address['lng'] && $address2['lng']){
                $distance = (int)distance($address['lat'],$address['lng'],$address2['lat'],$address2['lng'],'m');
                $taskOrder->distance = $distance;
                $taskOrder->save();
            }
        }
        $id = 0;
        if($isSaveYunTu) {
            $tableId = $yunTuBridge->getEmployerTableId();
            $yunTu = $this->getYunTu();
            $exist = $yunTuBridge->findYunTuTableDataByBusinessNo($tableId, $orderNo);
            $enableServices = $this->getDetailService()->getEnableServiceByOrderNo($orderNo);
            //搜索
            $customField['order_type'] = $taskOrder['order_type'];
            $customField['category'] = $taskOrder['category'];
            $customField['pay_price'] = $taskOrder['pay_price'];
            $customField['urge'] = isset($enableServices[OrderConst::SERVICE_PRICE_TYPE_URGE]) ? OrderConst::SERVICE_PRICE_TYPE_URGE : 0;
            $customField['face'] = isset($enableServices[OrderConst::SERVICE_PRICE_TYPE_FACE]) ? OrderConst::SERVICE_PRICE_TYPE_FACE : 0;
            $customField['insurance'] = isset($enableServices[OrderConst::SERVICE_PRICE_TYPE_INSURANCE]) ? OrderConst::SERVICE_PRICE_TYPE_INSURANCE : 0;
            if ($exist) {
                $id = $exist['table_business_id'];
                if ($address['lng'] && $address['lat']) {
                    $yunTu->update($tableId, $id, YunTu::LOC_TYPE_COORDINATE, $address['lng'], $address['lat'], $detailAddress, $taskOrder['order_name'], $orderNo, $customField);
                } else {
                    $yunTu->update($tableId, $id, YunTu::LOC_TYPE_ADDRESS, '', '', $detailAddress, $taskOrder['order_name'], $orderNo, $customField);
                }
            } else {
                if ($address['lng'] && $address['lat']) {
                    $id = $yunTu->insert($tableId, YunTu::LOC_TYPE_COORDINATE, $address['lng'], $address['lat'], $detailAddress, $taskOrder['order_name'], $orderNo, $customField);
                } else {
                    $id = $yunTu->insert($tableId, YunTu::LOC_TYPE_ADDRESS, '', '', $detailAddress, $taskOrder['order_name'], $orderNo, $customField);
                }
                $yunTuBridge->addYuTuTableData(YunTuService::BUSINESS_TYPE_EMPLOYER, $tableId, $id, $orderNo);
            }
        }
        return $id;
    }

    public function deleteEmployerYuTuAddressByOrderNo($orderNo){
        $yunTuBridge = $this->getYunTuBridge();
        $tableId = $yunTuBridge->getEmployerTableId();
        $exist = $yunTuBridge->findYunTuTableDataByBusinessNo($tableId,$orderNo);
        if(!$exist){
           return false;
        }
        $id = $exist['table_business_id'];
        $yunTu = $this->getYunTu();
        $yunTu->delete($tableId,[$id]);
        $exist->delete();
        return true;
    }
    public function updateAddressAltitude($orderNo,$id,$lng,$lat){
        return Address::where(['order_no'=>$orderNo,'order_address_id'=>$id])->update(['lng'=>$lng,'lat'=>$lat]);
    }

    public function getDetailAddress(array $data){
        if($data['city'] == '省直辖县级行政区划'){
            $data['city'] = '';
        }
        if($data['city'] == '自治区直辖县级行政区划'){
            $data['city'] = '';
        }
        if($data['region'] == '市辖区'){
            $data['region'] = '';
        }
        if($data['city'] == '市辖区'){
            $data['city'] = '';
        }
        $detail = $data['province'].$data['city'].$data['region'].$data['street'].$data['address_detail'];
        return $detail;
    }

    public function countTask($userId,array $state = []){
        return TaskOrder::where(['user_id'=>$userId])->when(!empty($state),function ($query) use($state){
            $query->whereIn('order_state',$state);
        })->count();
    }
    /**
     * @return YunTuService
     */
    protected function getYunTuBridge(){
        return new YunTuBridge(new YunTuService());
    }

    /**
     * @return YunTuInterface
     */
    protected function getYunTu(){
        return getYuntu();
    }
}
