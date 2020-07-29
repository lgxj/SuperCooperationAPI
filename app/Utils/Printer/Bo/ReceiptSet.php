<?php
/**
 * Created by PhpStorm.
 * User: suqian
 * Date: 17/11/16
 * Time: 上午10:24
 */

namespace App\Utils\Printer\Bo;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ReceiptSet
{

    private $cache = null;
    private $cacheKey = 'printer.receipt:%s:%s:%s';
    private $ttl = 0;

    public function __construct()
    {
        $this->cache = Redis::connection('cache');
    }

    /**
     * 获取小票编号
     * @param integer $businessId 标识身份,如用户ID,店铺ID
     * @param string $type 小票业务类型
     * @param string $orderId 订单号
     * @param string $dealTime 业务交易时间
     * @return \Generator|void
     */
    public function getReceiptNo($businessId, $type, $orderId, $dealTime)
    {
        //要打印的订单不是今天的
        if (date("Ymd", strtotime($dealTime)) < date("Ymd", time())) {
            return '--';
        }


        $today = $this->getToday();
        $keys = [$type,$businessId,$today];
        try {
            $list =  $this->cache->zRange($this->formatKey($keys), 0, -1);
            if (!$list) {
                $this->setReceiptNo($businessId, $type, $orderId);
                $total = $this->cache->zCard($this->formatKey($keys));
                $receiptNo = $total > 0 ? $total : 1;
                return $receiptNo;
            }
            //查找数据
            foreach ($list as $key => $value) {
                if ($value == $orderId) {
                    $receiptNo = $key + 1;
                    return $receiptNo;
                }
            }
            //如果没找到
            $this->setReceiptNo($businessId, $type, $orderId);
            $total =  $this->cache->zCard($this->formatKey($keys));
            $calTotal = count($list)+1;
            $receiptNo = $total > 0 ? $total : $calTotal;
            return $receiptNo;
        }catch(\Exception $e){
            Log::error('printer_receipt_error', ['param' => [$businessId,$orderId,$today], 'error' => $e->getMessage()]);
        }
        return 1;
    }

    /**
     * 设置小票编号
     * @param integer $businessId
     * @param string $type 小票类型
     * @param string $orderId 订单号或点餐id
     * @return \Generator|void
     */
    public function setReceiptNo($businessId, $type, $orderId)
    {
        $today = $this->getToday();
        try {
            $keys = [$type, $businessId, $today];
            $exist = $this->cache->zScore($this->formatKey($keys), $orderId);
            if (empty($exist)) {
                $score = round(microtime(true), 2) * 100;
                $this->cache->zAdd($this->formatKey($keys), $score, $orderId);
            }
        }catch(\Exception $e){
             Log::error('printer_receipt_error', ['param' => [$businessId,$orderId,$today], 'error' => $e->getMessage()]);
            return false;
        }
        return true;
    }

    /**
     * 返回今天的日期
     * @return string 171010
     */
    private function getToday()
    {
        return date("Ymd");
    }

    private function formatKey(array $params){
        return call_user_func_array('sprintf', array_merge([$this->cacheKey], $params));
    }
}
