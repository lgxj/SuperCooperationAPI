<?php


namespace App\Models\Trade\Order;


use App\Models\Trade\BaseTrade;

class StateChange extends BaseTrade
{
    protected $table = 'order_state_change';

    protected $primaryKey = 'state_change_id';

    protected $casts = [
        'order_no' => 'string'
    ];
}
