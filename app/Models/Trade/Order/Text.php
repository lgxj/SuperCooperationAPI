<?php


namespace App\Models\Trade\Order;


use App\Models\Trade\BaseTrade;

class Text extends BaseTrade
{
    protected $table = 'order_text';

    protected $primaryKey = 'text_id';

    protected $casts = [
        'order_no' => 'string'
    ];
}
