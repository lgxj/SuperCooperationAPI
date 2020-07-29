<?php
/**
 * Created by PhpStorm.
 * User: suqian
 * Date: 17/10/18
 * Time: 下午3:48
 */

namespace App\Utils\Printer\Devices\Command;


class FeieCommand extends ICommand
{

    /**
     * 换行
     * @param $str
     * @param $padType
     * @return \Generator
     */
    public function line($str='', $padType = STR_PAD_RIGHT)
    {
        $this->append($str. "<BR>");
        return $this;
    }

    public function center($str)
    {
        $this->append("<C>{$str}</C>");
        return $this;
    }

    public function centerStrong($str)
    {
        $this->append("<CB>{$str}</CB>");
        return $this;
    }

    public function strong($str)
    {
        $this->append("<B>{$str}</B>");
        return $this;
    }

    public function width($str)
    {
        $this->append("<W>{$str}</W>");
        return $this;
    }

    public function height($str)
    {
        $this->append("<L>{$str}</L>");
        return $this;
    }


    public function qrCode($str)
    {
        $this->append("<QR>{$str}</QR>");
        return $this;
    }

    public function logo($str)
    {
        $this->append("<LOGO>{$str}</LOGO>");
        return $this;
    }

    public function times($times = null)
    {
        return $this;
    }

    /**
     * 换行
     * @return $this
     */
    public function wrapHeight()
    {
        $this->wrapCommand('L');
        return $this;
    }

    /**
     * 换行
     * @return $this
     */
    public function wrapWidth()
    {
        $this->wrapCommand('W');
        return $this;
    }
}
