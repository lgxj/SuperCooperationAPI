<?php


namespace App\Utils;



class Sign
{

    /**
     * 获取签名
     * @author wzs
     * @param array $params
     * @param string $key
     * @return string
     */
    public static function getAmapSign(array $params,string $key,$urlEncode =false)
    {
        $unSignParaString = self::formatQueryParaMap($params, $urlEncode);
        $signStr = md5($unSignParaString .  $key);
        return $signStr;
    }

    /**
     * 格式化参数
     * @author wzs
     * @param array $paraMap
     * @param bool $urlEncode
     * @return bool|string
     */
    public static function formatQueryParaMap(array $paraMap,  $urlEncode = false)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if (null != $v && "null" != $v) {
                if ($urlEncode) {
                    $v = urlencode($v);
                }

                $buff .= $k . "=" . $v . "&";
            }
        }

        $reqPar = '';
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }

}
