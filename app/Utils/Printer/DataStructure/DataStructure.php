<?php
namespace App\Utils\Printer\DataStructure;

use App\Utils\Printer\Bo\TplDataSource;
use Illuminate\Support\Arr;

const TYPE_TEXT = 'text';//文本
const TYPE_QRCODE = 'qrcode';//二维码
const TYPE_LIST = 'list';//列表
const TYPE_PIC = 'pic';//图片
const TYPE_SPACE = 'space';//空行
const TYPE_LINE = 'line';//切割线

const SIZE_WIDTH = 'width';//加宽一倍
const SIZE_WIDTH_TWO = 'width_2';//加宽二倍
const SIZE_HEIGHT = 'height';//加高一倍
const SIZE_HEIGHT_TWO = 'height_2';//加高二倍
const SIZE_BIG = 'big';//放大
const SIZE_BIG_TWO = 'big_2';//放大二倍
const SIZE_NORMAL = 'default';//普通

const FACE_NORMAL = 'default';//普通
const FACE_STRONG = 'strong';//加粗

const ALIGN_LEFT = 'left';//居左
const ALIGN_RIGHT = 'right';//居右
const ALIGN_CENTER = 'center';//居中
const ALIGN_NORMAL = 'default';//普通

const WEIGHT_SIX = 6;
const WEIGHT_FIVE = 5;
const WEIGHT_FOUR = 4;
const WEIGHT_THREE= 3;
const WEIGHT_TWO = 2;
const WEIGHT_NORMAL = 1;



/**
 * 用于手机蓝牙打印数据结构
 *
 * Created by PhpStorm.
 * User: suqian
 * Date: 17/11/30
 * Time: 下午4:21
 */
abstract  class DataStructure
{

    protected $kdtId = 0;

    protected $orderNo = '';

    protected $extra = [];

    protected $structure = [];

    protected $deviceConfig = [];

    /**
     * @var TplDataSource
     */
    protected $dataSource = null;

    public function __construct($kdtId, $orderNo, array $extra = [],array $deviceConfig=[])
    {
        $this->kdtId = $kdtId;
        $this->orderNo = $orderNo;
        $this->extra = [];
        $this->deviceConfig = $deviceConfig;
        $this->dataSource = new TplDataSource($kdtId, $orderNo, $extra,$deviceConfig);
    }

    abstract protected function readData();

    abstract protected function build();

    protected function row($content,$contentType,$textAlign,$fontSize,$typeFace,$paperWeight){
        $this->structure[][] = [
            'content'=>$content,
            'contentType' => $contentType,
            'textAlign'  => $textAlign,
            'fontSize' => $fontSize,
            'typeFace' => $typeFace,
            'paperWeight' => $paperWeight
        ];
    }

    protected function listRow($content,$contentType,$textAlign,$fontSize,$typeFace,$paperWeight){
       $this->row($content,$contentType,$textAlign,$fontSize,$typeFace,$paperWeight);
    }

    protected function col($content,$contentType,$textAlign,$fontSize,$typeFace,$paperWeight){
        $rowCount = count($this->structure);
        $curRow = $rowCount >0 ? $rowCount-1 : $rowCount;
        if(!isset($this->structure[$curRow][0])){
            return;
        }
        $this->structure[$curRow][] = [
            'content'=>$content,
            'contentType' => $contentType,
            'textAlign'  => $textAlign,
            'fontSize' => $fontSize,
            'typeFace' => $typeFace,
            'paperWeight' => $paperWeight
        ];
    }

    protected function cutLine(){
        $this->row('-',TYPE_LINE,ALIGN_NORMAL,SIZE_NORMAL,FACE_NORMAL,WEIGHT_NORMAL);
    }

    protected function spaceLine(){
        $this->row(' ',TYPE_SPACE,ALIGN_NORMAL,SIZE_NORMAL,FACE_NORMAL,WEIGHT_NORMAL);
    }


    /**
     * @return string
     */
    protected function getShowType(){
        return Arr::get($this->deviceConfig,'showType',1);
    }

    /**
     * 58 80
     * @return int
     */
    protected function getPaperSize(){
       return Arr::get($this->deviceConfig,'paperSize',58);
    }

    //32 48
    protected function getContentSize(){
       return Arr::get($this->deviceConfig,'contentSize',32);
    }

    public function space($str, $expandLength, $padType = STR_PAD_RIGHT, $padStr = " ")
    {
        $diff = strlen( $str ) - mb_strlen( $str );
        $str = str_pad( $str, $expandLength + $diff/2, $padStr, $padType);
        return $str;
    }

    public function spaceFull($str,$padType,$padStr){
        $size = $this->getContentSize();
        return $this->space($str,$size,$padType,$padStr);
    }


    public function get()
    {
        $this->readData();
        $this->build();
        return $this->structure;
    }
}
