<?php


namespace App\Models\Trade\Fund;


use App\Models\Trade\BaseTrade;

class InoutLog extends BaseTrade
{
    protected $table = 'fund_inout_log';

    protected $primaryKey = 'inout_id';

    protected $casts = [
        'water_no' => 'string',
        'biz_no' => 'string',
        'relation_water_no' => 'string'
    ];
}
