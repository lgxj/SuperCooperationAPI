<?php


namespace App\Services\Trade\Order\Helper;


use App\Bridges\Pool\AddressBridge;
use App\Bridges\Pool\YunTuBridge;
use App\Bridges\User\UserBridge;
use App\Consts\Trade\OrderConst;
use App\Consts\UserConst;
use App\Exceptions\BusinessException;
use App\Models\Trade\Order\Cancel;
use App\Models\Trade\Order\ReceiverOrder;
use App\Models\Trade\Order\TaskOrder;
use App\Models\User\UserAddressPosition;
use App\Services\Pool\AddressService;
use App\Services\Pool\YunTuService;
use App\Services\User\UserService;
use App\Utils\Map\AMap;
use App\Utils\Map\YunTu;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * 帮手通过异步事件同步腾讯地点云
 *
 * Class HelperService
 * @package App\Services\Trade\Order\Helper
 */
class HelperService
{

    /**
     * @param array $address
     * @param $userId
     * @return int
     * @throws BusinessException
     */
    public function saveYuTuAddress(array $address,$userId){
        if(empty($address) || $userId <= 0){
            return 0;
        }
        $yunTuBridge = $this->getYunTuBridge();
        $tableId = $yunTuBridge->getHelperTableId();
        $detailAddress = $this->getDetailAddress($address);
        if($address['lng'] <=0 || $address['lat'] <= 0){
            $AMap = new AMap();
            $altitude = $AMap->getAltitudeByAddress($detailAddress);
            if($altitude) {
                list($address['lng'], $address['lat']) = $altitude;
            }
        }
        $user = $this->getUserBridge()->user($userId);
        if(empty($user)){
            Log::error("添加云图地址的用户不存在");
            return 0;
        }
        $yunTu = getYunTu();
        $exist = $yunTuBridge->findYunTuTableDataByBusinessNo($tableId,$userId);
        if($exist){
            $id = $exist['table_business_id'];
            if ($address['lng'] && $address['lat']) {
                $yunTu->update($tableId, $id,YunTu::LOC_TYPE_COORDINATE, $address['lng'], $address['lat'], $detailAddress, $user['user_name'], $userId);
            } else {
                $yunTu->update($tableId,$id, YunTu::LOC_TYPE_ADDRESS, '', '', $detailAddress,$user['user_name'], $userId);
            }
        }else {
            if ($address['lng'] && $address['lat']) {
                $id = $yunTu->insert($tableId, YunTu::LOC_TYPE_COORDINATE, $address['lng'], $address['lat'], $detailAddress, $user['user_name'], $userId);
            } else {
                $id = $yunTu->insert($tableId, YunTu::LOC_TYPE_ADDRESS, '', '', $detailAddress, $user['user_name'], $userId);
            }
            $yunTuBridge->addYuTuTableData(YunTuService::BUSINESS_TYPE_EMPLOYER,$tableId,$id,$userId);
        }
        $this->getUserBridge()->saveUserAddressPosition($address,$userId,$id);
        $return = $this->geAddressBridge()->getByName($address['province'],$address['city'],$address['region']);
        $return['position_id'] = $return;
        return $return;
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
        return ReceiverOrder::where(['user_id'=>$userId])->when(!empty($state),function ($query) use($state){
            $query->whereIn('receive_state',$state);
        })->count();
    }

    public function countTodayTask($userId,array $state = []){
        $endDay = Carbon::now()->endOfDay();
        $startDay = Carbon::now()->startOfDay();
        return TaskOrder::where(['helper_user_id'=>$userId])
                ->when(!empty($state),function ($query) use($state){
                if(count($state) > 1) {
                    $query->whereIn('order_state', $state);
                }else{
                    $query->where('order_state',$state[0]);
                }
            })
            ->whereBetween('success_time',[$startDay,$endDay])
            ->count();
    }

    public function countTodayCancelTotal($userId){
        $endDay = Carbon::now()->endOfDay();
        $startDay = Carbon::now()->startOfDay();
        return Cancel::where('user_id',$userId)
            ->where('user_type',UserConst::TYPE_HELPER)
            ->whereBetween('created_at',[$startDay,$endDay])
            ->count();
    }

    public function countWeekCancelTotal($userId){
        $endDay = Carbon::now()->endOfWeek();
        $startDay = Carbon::now()->startOfWeek();
        return Cancel::where('user_id',$userId)
            ->where('user_type',UserConst::TYPE_HELPER)
            ->whereBetween('created_at',[$startDay,$endDay])
            ->count();
    }

    /**
     * @return YunTuService
     */
    protected function getYunTuBridge(){
        return new YunTuBridge(new YunTuService());
    }

    /**
     * @return UserService
     */
    protected function getUserBridge(){
        return new UserBridge(new UserService());
    }

    /**
     * @return AddressService
     */
    protected function geAddressBridge(){
        return new AddressBridge(new AddressService());
    }
}
