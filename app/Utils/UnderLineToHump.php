<?php


namespace App\Utils;


class UnderLineToHump
{


    /**
     * 下划线转驼峰 字符串转化函数 _make_by_id_ => makeById
     *
     * @param $str
     *
     * @return string $str string 输出转化后的字符串
     */
    public static  function underLineToHump($str)
    {
        $str = trim($str, '_');//去除前后下划线_
        $len = strlen($str);
        $out = strtolower($str[0]);
        for ($i = 1; $i < $len; $i++) {
            if (ord($str[$i]) == ord('_')) {//如果当前是下划线，去除，并且下一位大写
                $out .= isset($str[$i + 1]) ? strtoupper($str[$i + 1]) : '';
                $i++;
            } else {
                $out .= $str[$i];
            }
        }
        return $out;
    }



    /**
     * 驼峰转下划线 字符串函数 MakeById => make_by_id
     * @param $str
     *
     * @return string
     */
    public static  function humpToUnderLine($str)
    {
        $len = strlen($str);
        $out = strtolower($str[0]);
        for ($i=1; $i<$len; $i++) {
            if(ord($str[$i]) >= ord('A') && (ord($str[$i]) <= ord('Z'))) {
                $out .= '_'.strtolower($str[$i]);
            }else{
                $out .= $str[$i];
            }
        }
        return $out;
    }

    /**
     * 驼峰式 与 下划线式 转化
     * @param string $str  字符串
     * @param string $mode 转化方法 hump驼峰|line下划线
     *
     * @return mixed|null|string|string[]
     */
    static public  function pregConvertString($str,$mode='hump'){
        if(empty($str)){
            return '';
        }
        switch ($mode){
            case 'hump'://下划线转驼峰
                $str    = preg_replace_callback('/[-_]+([a-z]{1})/',function($matches){
                    return strtoupper($matches[1]);
                },$str);
                $str    = ucfirst($str);//首字母大写
                break;
            case 'line'://驼峰转下划线
                $str = str_replace("_", "", $str);
                $str = preg_replace_callback('/([A-Z]{1})/',function($matches){
                    return '_'.strtolower($matches[0]);
                },$str);
                $str = trim($str,'_');
                break;
            default:
                echo 'mode is error!';
        }
        return $str;
    }


}
