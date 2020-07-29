<?php


namespace App\Models\Trade\Order;


use App\Consts\Trade\PayConst;
use App\Models\Trade\BaseTrade;

class Service extends BaseTrade
{
    protected $table = 'order_service';

    protected $primaryKey = 'service_id';

    protected $casts = [
        'order_no' => 'string'
    ];

    public function getByServiceType($orderNo,$serviceType){
        $data = $this->where(['order_no'=>$orderNo,'service_type'=>$serviceType])->first();
        return $data ? $data->toArray() : [];
    }

    public function getByOrderNo($orderNo){
        return $this->where('order_no',$orderNo)->get()->keyBy('service_type')->toArray();
    }

    public function sumEnableServiceByOrderNo($orderNo,array $serviceTypes = []){
        if(empty($orderNo)){
            return [];
        }
        $query = $this->where(['order_no'=>$orderNo,'pay_state'=>PayConst::STATE_PAY]);
        $query->when(!empty($serviceTypes),function ($query) use ($serviceTypes) {
            $query->whereIn('service_type',$serviceTypes);
             }
        );
        return $query->sum('service_price');

    }
}
