<?php
/**
 * Created by PhpStorm.
 * User: chenshengkun
 * Date: 2017/12/8
 * Time: 上午11:31
 */
namespace App\Utils\Printer\Bo\TplDataSource\Cash;


use App\Utils\Printer\Bo\ReceiptSet;
use App\Utils\Printer\Bo\TplDataSource\GeneralTplDataSource;


class Cash extends GeneralTplDataSource
{
    public function output($kdtId, $orderNo)
    {
        $output = array();
        $output['orderNo'] = $orderNo;

        //店铺
        $output['shopName'] = '苏苏餐饮店';

        //订单


        $output['payWay'] ="支付宝";
        $output['payTime'] = date("Y-m-d H:i:s");

        $output['totalFee'] = display_price(100);
        $output['payFee'] = display_price(200);
        $output['discountFee'] = display_price(11); //优惠金额
        $output['discountWay'] = '优惠券';
        $receipt = new ReceiptSet();
        $output['receiptNo'] =$receipt->getReceiptNo($kdtId, "cash", $orderNo, time());//小票编号

        return $output;
    }
}
