<?php
/**
 * Created by PhpStorm.
 * User: suqian
 * Date: 17/10/18
 * Time: 下午3:49
 */

namespace App\Utils\Printer\Devices\Command
;



class YiLianCommand extends ICommand
{

    /**
     * 换行
     * @param $str
     * @param $padType
     * @return \Generator
     */
    public function line($str='', $padType = STR_PAD_RIGHT)
    {
        $this->append($str. "\n");
        return $this;
    }

    public function center($str)
    {
        $this->append("<center>{$str}</center>");
        return $this;
    }

    public function centerStrong($str)
    {
        $this->append("<FH2><FB><center>{$str}</center></FB></FH2>");
        return $this;
    }

    public function strong($str)
    {
        $this->append("<FS2><FB>{$str}</FB></FS2>");
        return $this;
    }

    public function width($str)
    {
        $this->append("<FW2>{$str}</FW2>");
        return $this;
    }

    public function height($str)
    {
        $this->append("<FH2>{$str}</FH2>");
        return $this;
    }


    public function qrCode($str)
    {
        $this->append("<QR>{$str}</QR>");
        return $this;
    }

    public function logo($str)
    {
        return $this;
    }

    public function times($times = null)
    {
        if($times <= 0 || !$times){
            $printTimes = $this->device->getPrintTimes();
        }else{
            $printTimes = $times;
        }
        $this->append("<MN>{$printTimes}</MN>\n");
        return $this;
    }

    /**
     * 换行
     * @return $this
     */
    public function wrapHeight()
    {
        $this->wrapCommand('FH');
        return $this;
    }

    /**
     * 换行
     * @return $this
     */
    public function wrapWidth()
    {
        $this->wrapCommand('FW');
        return $this;
    }
}
