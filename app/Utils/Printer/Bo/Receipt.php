<?php
/**
 * Created by PhpStorm.
 * User: chenshengkun
 * Date: 2017/10/24
 * Time: 上午11:25
 */

namespace App\Utils\Printer\Bo;



use Illuminate\Support\Facades\Cache;
use App\Utils\Printer\Conf\Printer as PrinterConf;
use Psr\SimpleCache\InvalidArgumentException;

class Receipt
{
    private $receiptNo = 0;//小票编号
    private $cacheKey = 'printer.receipt.shop:%s:%s';

    /**
     * 获取小票编号
     * @param integer $businessId
     * @param string $type 小票类型
     * @param string $orderId 订单号或点餐id
     * @param string $dealTime 交易时间
     * @return \Generator|void
     */
    public function getReceiptNo($businessId, $type, $orderId, $dealTime)
    {
        //要打印的订单不是今天的
        if (date("Ymd", strtotime($dealTime)) < date("Ymd", time())) {
            return PrinterConf::NULL_RECEIPT_NO;
        }

        //已经获取过直接返回
        if ($this->receiptNo) {
            return $this->receiptNo;
        }

        $today = $this->getToday();
        $keys = [$type, $businessId];
        $data =  Cache::get($this->formatKey($keys));

        //缓存中的数据不是今天的
        if (!$data || (isset($data['lastPrintDay']) && $data['lastPrintDay'] < $today) || empty($data['list'])) {
            $this->receiptNo = PrinterConf::MIN_RECEIPT_NO;
        } else {
            //查找数据
            $found = false;
            foreach ($data['list'] as $key => $value) {
                if ($value == $orderId) {
                    $found = true;
                    $this->receiptNo = $key + 1;
                    break;
                }
            }

            //如果没找到
            if (!$found) {
                $this->receiptNo = count($data['list']) + 1;
            }
        }

        return $this->receiptNo;
    }

    /**
     * 设置小票编号
     * @param integer $businessId
     * @param string $type 小票类型
     * @param string $orderId 订单号或点餐id
     * @return \Generator|void
     * @throws InvalidArgumentException
     */
    public function setReceiptNo($businessId, $type, $orderId)
    {
        $today = $this->getToday();
        $keys = [$type, $businessId];
        $data =  Cache::get($this->formatKey($keys));

        //缓存中的数据不是今天的
        if (!$data || (isset($data['lastPrintDay']) && $data['lastPrintDay'] < $today) || empty($data['list'])) {
            $data = [
                'list' => [$orderId],
                'lastPrintDay' => $today
            ];
            return Cache::set($this->formatKey($keys), $data);
        } else {
            $found = false;
            foreach ($data['list'] as $key => $value) {
                if ($value == $orderId) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $data['list'][] = $orderId;
                $data['lastPrintDay'] = $today;
                return Cache::set($this->formatKey($keys), $data);
            }
        }
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
