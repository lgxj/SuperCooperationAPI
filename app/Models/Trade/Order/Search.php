<?php


namespace App\Models\Trade\Order;


use App\Models\Trade\BaseTrade;

/**
 * 任务大厅搜索覆盖索引表
 *
 * Class Search
 * @package App\Models\Trade\Order
 */
class Search extends BaseTrade
{
    public $timestamps = false;

    protected $table = 'order_search';

    protected $primaryKey = 'order_no';

    protected $casts = [
        'order_no' => 'string'
    ];
}
