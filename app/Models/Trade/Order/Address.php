<?php


namespace App\Models\Trade\Order;


use App\Models\Trade\BaseTrade;

class Address extends BaseTrade
{
    protected $table = 'order_address';

    protected $primaryKey = 'user_address_id';

    protected $casts = [
        'order_no' => 'string'
    ];
}
