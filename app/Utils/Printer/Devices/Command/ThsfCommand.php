<?php
/**
 * Created by PhpStorm.
 * User: suqian
 * Date: 17/10/18
 * Time: 下午3:48
 */

namespace App\Utils\Printer\Devices\Command;


class ThsfCommand extends ICommand
{
    /**
     * 换行
     * @param $str
     * @param $padType
     * @return \Generator
     */
    public function line($str='', $padType = STR_PAD_RIGHT)
    {
        $printType = $this->device->getDeviceType();
        if ($printType == 1) {
            $this->append($str."\n");
        } else {
            $this->append($str. "<BR>");
        }
        return $this;
    }

    public function center($str)
    {
        $printType = $this->device->getDeviceType();
        if ($printType == 1) {
            $this->space($str,$this->paperSize,STR_PAD_BOTH);
        }else {
            $this->append("<C>{$str}</C>");
        }
        return $this;
    }

    public function centerStrong($str)
    {
        $printType = $this->device->getDeviceType();
        if ($printType == 1) {
            $this->space($str,$this->paperSize,STR_PAD_BOTH);
            $this->shiftContent('^H2');
        }else {
            $this->append("<CB>{$str}</CB>");
        }
        return $this;
    }

    public function strong($str)
    {
        $printType = $this->device->getDeviceType();
        if ($printType == 1) {
            $strLen = mb_strlen($str);
            for ($i = 0; $i < $strLen; $i += 15) {
                $lineStr = mb_substr($str, $i, 15, "utf-8");
                $this->append("^H2{$lineStr}")->line();
            }
        }else {
            $this->append("<B>{$str}</B>");
        }
        return $this;
    }

    public function width($str)
    {
        $printType = $this->device->getDeviceType();
        if ($printType == 1) {
            $this->append("^W2{$str}");
        }else {
            $this->append("<W>{$str}</W>");
        }
        return $this;
    }

    public function height($str)
    {
        $printType = $this->device->getDeviceType();
        if ($printType == 1) {
            $this->append("^H2{$str}");
        }else {
            $this->append("<L>{$str}</L>");
        }
        return $this;
    }


    public function qrCode($str)
    {
        $printType = $this->device->getDeviceType();
        if ($printType== 1) {
            $this->append("^Q +{$str}");
        }else {
            $this->append("<QR>{$str}</QR>");
        }
        return $this;
    }

    public function logo($str)
    {
        return $this;
    }

    public function times($times = null)
    {
        $printType = $this->device->getDeviceType();
        if ($printType == 1) {
            if($times <= 0 || !$times){
                $printTimes = $this->device->getPrintTimes();
            }else{
                $printTimes = $times;
            }
            $this->append("^N{$printTimes} \n");
        }
        return $this;
    }

    /**
     * 换行
     * @return $this
     */
    public function wrapHeight()
    {
        $printType = $this->device->getDeviceType();
        if ($printType == 1) {
            $this->shiftContent("^H2");
        }else {
            $this->wrapCommand("L");
        }
        return $this;
    }

    /**
     * 换行
     * @return $this
     */
    public function wrapWidth()
    {
        $printType = $this->device->getDeviceType();
        if ($printType== 1) {
            $this->shiftContent("^W2");
        }else {
            $this->wrapCommand("W");
        }
        return $this;
    }
}
