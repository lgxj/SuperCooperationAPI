<?php


namespace App\Services\User;


use App\Bridges\Pool\AddressBridge;
use App\Consts\ErrorCode\UserErrorCode;
use App\Exceptions\BusinessException;
use App\Models\User\UserAddress;
use App\Utils\Map\AMap;
use Illuminate\Support\Facades\Validator;

/**
 * 用户地址库服务层
 *
 * Class AddressService
 * @package App\Services\User
 */
class AddressService
{
    /**
     * @var \App\Services\Pool\AddressService
     */
    protected  $addressBridge;

    const MAX_ADDRESS = 10;

    public function __construct()
    {
        $this->addressBridge = new AddressBridge(new \App\Services\Pool\AddressService());
    }

    /**
     * @param array $data
     * @return BusinessException|array
     * @throws BusinessException
     */
    public function add(array $data)
    {
        $validate = Validator::make($data,[
            'address_id'=>'required|integer',
            'user_id'=>'required|integer',
            'user_name'=>'required',
            'user_phone'=>'required',
            'province'=>'required',
            'city'=>'required',
            'region'=>'required',
            'address_detail'=>'required'
        ],[
            'user_id.required' => '用户标识不能为空',
            'address_id.required' => '地区标识不能为空',
            'user_name.required' => '姓名不能为空',
            'user_phone.required'=>"电话不能为空",
            'province.required'=>"省份不能为空",
            'city.required'=>'城市不能为空',
            'region.required' => "城市或区域不能为空",
            'address_detail.required' => "详细地址不能为空"
        ]);
        if($validate->fails()){
            throw new BusinessException($validate->errors()->first(),UserErrorCode::ADDRESS_VALIDATION_ERROR);
        }
        $user_id = $data['user_id'];
        $address_id = $data['address_id'];
        $address = $this->addressBridge->getByGovAreaId($address_id);
        if (empty($address)) {
            throw new BusinessException("地址库中不存在此地址",UserErrorCode::ADDRESS_LIB_NOT_EXIST);
        }
        $userTotalAddress = $this->userTotalAddress($user_id);
        if($userTotalAddress >= self::MAX_ADDRESS){
            throw new BusinessException("您最多只能添加10个地址",UserErrorCode::ADDRESS_MAX_LIMIT);
        }
        $addressModel = new UserAddress();
        $fields = $addressModel->getTableColumns();
        foreach ($fields as $field) {
            if ($field == $addressModel->getKeyName()) {
                continue;
            }
            if (isset($data[$field])) {
                $addressModel->$field = $data[$field];
            }
        }
        if(empty($data['lng']) || empty($data['lat'])){
            $altitude = calcAltitude($data);
            $altitude && list($addressModel->lng,$addressModel->lat) = $altitude;
        }
        $flag = $addressModel->save();
        if($flag && !empty($data['is_default'])){
            $this->setDefault($user_id,$addressModel->id);
        }
        return $addressModel->toArray();
    }

    /**
     * @param array $data
     * @return BusinessException|array
     * @throws BusinessException
     */
    public function update(array $data)
    {
        $validate = Validator::make($data,[
            'address_id'=>'required|integer',
            'user_id'=>'required|integer',
            'id'=>'required|integer',
            'user_name'=>'required',
            'user_phone'=>'required',
            'province'=>'required',
            'city'=>'required',
            'region'=>'required',
            'address_detail'=>'required'
        ],[
            'user_id.required' => '用户标识不能为空',
            'address_id.required' => '地区标识不能为空',
            'user_name.required' => '姓名不能为空',
            'user_phone.required'=>"电话不能为空",
            'province.required'=>"省份不能为空",
            'city.required'=>'城市不能为空',
            'region.required' => "城市或区域不能为空",
            'address_detail.required' => "详细地址不能为空"
        ]);
        if($validate->fails()){
            throw new BusinessException($validate->errors()->first());
        }
        $id = $data['id'];
        $user_id = $data['user_id'];
        $address_id = $data['address_id'];
        $address = $this->addressBridge->getByGovAreaId($address_id);
        if (empty($address)) {
            throw new BusinessException("地址库中不存在此地址");
        }
        $userAddress = UserAddress::find($id);
        if(empty($userAddress)){
            throw new BusinessException("地址不存在");
        }
        $fields = $userAddress->getTableColumns();
        foreach ($fields as $field) {
            if ($field == $userAddress->getKeyName()) {
                continue;
            }
            if (isset($data[$field])) {
                $userAddress->$field = $data[$field];
            }
        }
        if(empty($data['lng']) || empty($data['lat'])){
            $altitude = calcAltitude($data);
            $altitude && list($userAddress->lng,$userAddress->lat) = $altitude;
        }
        $flag = $userAddress->save();
        if($flag && !empty($data['is_default'])){
            $this->setDefault($user_id,$userAddress->id);
        }
        return $userAddress->toArray();
    }


    public function remove(int $userId ,int $id) : bool
    {
        $flag = UserAddress::where(['user_id'=>$userId,'id'=>$id])->delete();
        return $flag > 0 ? true : false;
    }

    public function setDefault(int $userId ,int $id) : bool {
        if($userId <= 0 || $id <= 0){
            return false;
        }
        UserAddress::where(['user_id'=>$userId])->update(['is_default'=>0]);
        UserAddress::where(['user_id'=>$userId,'id'=>$id])->update(['is_default'=>1]);
        return true;
    }

    public function find(int $userId,int $id) : array
    {
        if($userId <= 0 || $id <= 0){
            return [];
        }
        $data = UserAddress::where(['user_id'=>$userId,'id'=>$id])->first();
        return $data ? $data->toArray() : [];
    }

    public function findAll(int $userId) : array
    {
        if($userId <= 0){
            return [];
        }
        $data = UserAddress::where(['user_id'=>$userId])->orderByDesc('id')->get();
        return $data ? $data->toArray() : [];
    }

    public function userTotalAddress(int $userId) : int
    {
        return UserAddress::where(['user_id'=>$userId])->count();
    }


}
