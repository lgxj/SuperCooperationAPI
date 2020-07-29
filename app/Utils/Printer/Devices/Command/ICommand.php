<?php
namespace App\Utils\Printer\Devices\Command;

use App\Utils\Printer\Devices\Device;

/**
 * Created by PhpStorm.
 * User: suqian
 * Date: 17/10/17
 * Time: 下午7:58
 */
abstract class ICommand
{
    protected $stringArray = [];

    protected $paperSize = 32;

    /**
     * @var Device
     */
    protected $device = null;

    public function __construct(Device $device)
    {
        $this->device = $device;
    }

    /**
     * 填充空格
     * @param $str
     * @param $padStr
     * @param $padType
     * @param $expandLength
     * @return $this
     */
    public function space($str, $expandLength, $padType = STR_PAD_RIGHT, $padStr = " ")
    {
        $diff = strlen( $str ) - mb_strlen( $str );
        $str = str_pad( $str, $expandLength + $diff/2, $padStr, $padType);
        $this->append($str);
        return $this;
    }

    /**
     * 填充空格
     * @param $str
     * @param $padStr
     * @param $padType
     * @param $expandLength
     * @return string
     */
    public function spaceUnAppend($str, $expandLength, $padType = STR_PAD_RIGHT, $padStr = " ")
    {
        $diff = strlen( $str ) - mb_strlen( $str );
        $str = str_pad( $str, $expandLength + $diff/2, $padStr, $padType);
        return $str;
    }
    /**
     * 换行
     * @param $str
     * @param $padType
     * @return $this
     */
    public abstract function line($str='', $padType = STR_PAD_RIGHT);

    /**
     * 换行
     * @param $str
     * @return $this
     */
    public abstract function center($str);

    /**
     * 换行
     * @param $str
     * @return $this
     */
    public abstract function centerStrong($str);

    /**
     * 换行
     * @param $str
     * @return $this
     */
    public abstract function strong($str);

    /**
     * 换行
     * @param $str
     * @return $this
     */
    public abstract function width($str);

    /**
     * 换行
     * @param $str
     * @return $this
     */
    public abstract function height($str);
    /**
     * 换行
     * @param $str
     * @return $this
     */
    public abstract function qrCode($str);
    /**
     * 换行
     * @param $str
     * @return $this
     */
    public abstract function logo($str);
    /**
     * 换行
     * @param $times
     * @return $this
     */
    public abstract function times($times = null);

    /**
     * 换行
     * @return $this
     */
    public abstract function wrapHeight();

    /**
     * 换行
     * @return $this
     */
    public abstract function wrapWidth();

    /**
     * 获取模板
     * @return string
     */
    public function getTemplate()
    {
        return implode('',$this->stringArray);
    }

    public function getDeviceNo(){
        return $this->deviceNo;
    }

    public function cutLine(){
        $this->line('--------------------------------');
    }

    /**
     * 换行
     * @param $content
     * @return $this
     */
    public function shiftContent($content){
        $currentIndex = count($this->stringArray) - 1;
        if(isset($this->stringArray[$currentIndex])){
            $str = $this->stringArray[$currentIndex];
            $str = $content.$str;
            $this->stringArray[$currentIndex] = $str;
        }
        return $this;
    }

    /**
     * 换行
     * @param $content
     * @return $this
     */
    public function appendContent($content){
        $currentIndex = count($this->stringArray) - 1;
        if(isset($this->stringArray[$currentIndex])){
            $str = $this->stringArray[$currentIndex];
            $str = $str.$content;
            $this->stringArray[$currentIndex] = $str;
        }
        return $this;
    }

    /**
     * 换行
     * @param $command
     * @return $this
     */
    public function wrapCommand($command){
        $currentIndex = count($this->stringArray) - 1;
        if(isset($this->stringArray[$currentIndex])){
            $str = $this->stringArray[$currentIndex];
            $str = "<{$command}>".$str."</{$command}>";
            $this->stringArray[$currentIndex] = $str;
        }
        return $this;
    }


    public function current(){
        $currentIndex = count($this->stringArray) - 1;
        if(isset($this->stringArray[$currentIndex])){
            return $this->stringArray[$currentIndex];
        }
        return null;
    }

    /**
     * @param $str
     * @return $this
     */
    protected function append($str){
        $this->stringArray[] = $str;
        return $this;
    }

    /**
     * @return Device
     */
    public function getDevice(){
        return $this->device;
    }

}
