<?php


namespace App\Models\Trade\Fund;


use App\Models\Trade\BaseTrade;

class FreezeLog extends BaseTrade
{
    protected $table = 'fund_freeze_log';

    protected $primaryKey = 'freeze_id';

    protected $casts = [
        'water_no' => 'string'
    ];

}
