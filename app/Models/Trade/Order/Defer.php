<?php


namespace App\Models\Trade\Order;


use App\Models\Trade\BaseTrade;

class Defer extends BaseTrade
{
    protected $table = 'order_defer';

    protected $primaryKey = 'defer_id';
}
