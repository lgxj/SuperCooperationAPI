<?php

namespace App\Utils\Printer\Common;



use App\Utils\Printer\Bo\ReceiptSet;
use App\Utils\Printer\Devices\Device;
use App\Utils\Printer\Devices\FeieDevice;
use App\Utils\Printer\Devices\ThsfDevice;
use App\Utils\Printer\Devices\YiLianDevice;

/**
 * Created by PhpStorm.
 * User: suqian
 * Date: 17/10/23
 * Time: 下午4:30
 */
class Help
{

    /**
     * @param $categoryCode
     * @param array $device
     * @return Device
     */
    public function getDeviceByTypeCode($categoryCode, array $device)
    {
        if ($categoryCode == 2) {
            return new ThsfDevice($device);
        } elseif ($categoryCode == 0) {
            return new FeieDevice($device);
        } elseif ($categoryCode == 1) {
            return new YiLianDevice($device);
        }
    }



    public function getReceiptBo(){
        return new ReceiptSet();
    }
}
