<?php
/**
 * Created by PhpStorm.
 * User: suqian
 * Date: 17/12/1
 * Time: 上午10:09
 */

namespace App\Utils\Printer\Bo\Traits;


trait TakeawayAttribute
{

    private $title = "外卖";
    private $shopName = '';
    private $receiptNo = '-';
    private $payment = '';
    private $payTime = '';
    private $buyerMemo = '';
    private $sellerMemo = '';
    private $goodsList = [];
    private $discountWay = '';
    private $discountFee = 0;
    private $postFee = 0;
    private $receiverName = '';
    private $receiverPhone = '';
    private $receiverAddress = '';
    private $totalNum = 0;
    private $payFee = 0;
    private $invoiceTitle = '';
    private $taxNo = '';
    private $boxPrice = 0;
    private $totalOriginPrice = 0;
    private $goodsListInGroup = [];
    private $expectReceiveTime = '';

}
