<?php
/**
 * Created by PhpStorm.
 * User: chenshengkun
 * Date: 2017/12/12
 * Time: 上午10:15
 */

namespace App\Utils\Printer\Bo\TplDataSource;

abstract class GeneralTplDataSource
{
    /**
     * 价格小数后 抹0
     * @param $float
     * @return float
     */
    protected function removeFloatZero($float)
    {
        $float = sprintf("%.2f", $float);
        return sprintf("%s", $float * 1);
    }

    /**
     * 获取优惠方式
     * @param $order
     * @return array
     */
    protected function getDiscount($order)
    {
        $discount = [];
        $decrease = 0;
        $discountConditions = array(); //优惠方式
        $index = 0;
        if (!empty($order["umpInfo"]["orderActivities"])) {
            foreach ($order["umpInfo"]["orderActivities"] as $key =>$activity) {
                if ($activity["type"] == "couponCard") {
                    $discountConditions[$index++] = "优惠券";
                } elseif ($activity["type"] == "meetReduce") {
                    $discountConditions[$index++] = "满减";
                }
                $decrease += $activity['decrease'];
            }
        }
        $discount['discountWay'] = empty($discountConditions) ? "无" : implode("，", $discountConditions);
        $discount['decrease'] = $decrease;

        return $discount;
    }

    protected function appendGoodsList($orderDetail, &$totalOriginFee, &$totalNum)
    {
        //商品列表
        $totalOriginFee = 0;
        $totalNum = 0;
        $goodsList = array();

        foreach ($orderDetail['itemInfo'] as $goods) {
            //sku信息
            $tag = array();
            if (!empty($goods['sku'])) {
                $tag[] = $goods['sku'][0]['v'];
            }
            //限时折扣
            $hasDiscounted = 0;
            if ($goods['originUnitPrice'] != $goods['unitPrice']) {
                $hasDiscounted = 1;
                $goods['title'] = "[限时折扣] " . $goods['title'];
            }

            if (!empty($goods['attributes'])) {
                foreach ($goods['attributes'] as $attribute => $num) {
                    $goodsTitle = $goods['title'];
                    if ($attribute != '_') {
                        $newTag = array_merge($tag, explode('`', $attribute));
                        $goodsTitle .= empty($newTag) ? '' : ('(' . implode(' ', $newTag). ')');
                    } else {
                        $goodsTitle .= empty($tag) ? '' : ('(' . implode(' ', $tag). ')');
                    }

                    $goodsList[] = [
                        'goodsId' => $goods['goodsId'],
                        'title' => $goodsTitle,
                        'num' => $num,
                        'price' => $this->removeFloatZero($goods['originUnitPrice'] / 100.0),//老版本app使用原价
                        'originPrice' => $this->removeFloatZero($goods['originUnitPrice'] * $num / 100.0),//商品原总价
                        'payPrice' => $this->removeFloatZero($goods['unitPrice'] * $num / 100.0),//支付的总价
                        'hasDiscounted' => $hasDiscounted
                    ];
                }
            } else {
                $goodsTitle = $goods['title'];
                $goodsTitle .= empty($tag) ? '' : ('(' . implode(' ', $tag). ')');
                $goodsList[] = [
                    'goodsId' => $goods['goodsId'],
                    'title' => $goodsTitle,
                    'num' => $goods['num'],
                    'price' => $this->removeFloatZero($goods['originUnitPrice'] / 100.0),//老版本app使用原价
                    'originPrice' => $this->removeFloatZero($goods['originUnitPrice'] * $goods['num'] / 100.0),//商品原总价
                    'payPrice' => $this->removeFloatZero($goods['unitPrice'] * $goods['num'] / 100.0),//支付的总价
                    'hasDiscounted' => $hasDiscounted
                ];
            }

            $totalOriginFee += $goods['originUnitPrice'] * $goods['num'];
            $totalNum += $goods['num'];
        }

        return $goodsList;
    }
}
