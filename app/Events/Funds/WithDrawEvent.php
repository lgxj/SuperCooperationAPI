<?php


namespace App\Events\Funds;


use Illuminate\Queue\SerializesModels;

class WithDrawEvent
{
    use SerializesModels;

    public $userId = 0;

    public $status = 0;

    public $withDrawId = 0;


    public function __construct( $userId,$status,$withDrawId)
    {
        $this->userId = $userId;
        $this->status = $status;
        $this->withDrawId = $withDrawId;
    }
}
