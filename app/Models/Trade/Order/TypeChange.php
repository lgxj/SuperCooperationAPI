<?php


namespace App\Models\Trade\Order;


use App\Models\Trade\BaseTrade;

class TypeChange extends BaseTrade
{
    protected $table = 'order_type_change';

    protected $primaryKey = 'type_change_id';

    protected $casts = [
        'order_no' => 'string'
    ];
}
