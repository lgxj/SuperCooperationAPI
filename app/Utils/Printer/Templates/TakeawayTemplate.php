<?php
/**
 * Created by PhpStorm.
 * User: suqian
 * Date: 17/10/18
 * Time: 下午3:49
 */

namespace App\Utils\Printer\Templates;

use App\Utils\Printer\Bo\Traits\TakeawayAttribute;
use App\Utils\Printer\Conf\Printer;
use App\Utils\Printer\Devices\Command\ICommand;

class TakeawayTemplate extends Template
{
    use TakeawayAttribute;

    protected function template()
    {
        $command = $this->getCommand();
        $showType = $command->getDevice()->getShowType();
        $command->times();
        $command->centerStrong($this->title."#" . $this->receiptNo)->line();
        $command->center($this->shopName)->line();
        if ($this->expectReceiveTime) {
            $command->cutLine();
            $command->centerStrong("预定 ".$this->expectReceiveTime." 送达")->line();
        }
        //订单号、支付方式、交易时间
        $command->cutLine();
        $command->line('订单号：' . $this->orderNo);
        $command->line('支付方式：' . $this->payment);
        $command->line('交易时间：' . $this->payTime);
        $command->cutLine();

        $tableTitle = $command->spaceUnAppend('商品名称', 20) . $command->spaceUnAppend('数量', 4, STR_PAD_LEFT) . $command->spaceUnAppend('金额', 8, STR_PAD_LEFT);
        $command->line($tableTitle);
        $command->cutLine();

        if ($showType == Printer::SHOW_TYPE_BY_ORDER) {
            $this->showGoodsListByOrder($command);
        } elseif ($showType == Printer::SHOW_TYPE_BY_GROUP) {
            $this->showGoodsListByGroup($command);
        }

        if ($this->boxPrice > 0) {
            $boxPriceTitle = $command->spaceUnAppend('餐盒费', 18) . $command->spaceUnAppend(" ", 6, STR_PAD_LEFT) . $command->spaceUnAppend($this->boxPrice, 8, STR_PAD_LEFT);
            $command->line($boxPriceTitle);
        }

        $postFeeTitle = $command->spaceUnAppend('配送费', 18) . $command->spaceUnAppend($this->postFee, 14, STR_PAD_LEFT);
        $command->line($postFeeTitle);
        $command->cutLine();
        $command->line('订单原价：' . $this->totalOriginPrice . "元");
        $command->cutLine();

        if ($this->discountFee > 0) {
            $command->line('优惠方式：' . $this->discountWay);
            $command->line('优惠金额：' . $this->discountFee);
            $command->line();
        }

        $command->height("共 {$this->totalNum} 件，实付 {$this->payFee} 元")->line();
        $command->cutLine();

        if ($this->buyerMemo || $this->sellerMemo) {
            $this->buyerMemo && $command->strong("买家留言：{$this->buyerMemo}")->line();
            $this->sellerMemo && $command->strong("商家备注：{$this->sellerMemo}")->line();
            $command->cutLine();
        }

        if ($this->invoiceTitle) {
            if ($this->taxNo) {
                $this->invoiceTitle = $this->invoiceTitle . ' | ' . $this->taxNo;
            }
            $command->line("发票信息：{$this->invoiceTitle}");
            $command->cutLine();
        }

        $command->strong($this->receiverAddress)->line();
        $command->strong($this->receiverPhone)->line();
        $command->strong($this->receiverName)->line();

        return $command->getTemplate();
    }

    protected function readData()
    {
        $tplData =  $this->dataSource->takeaway();
        foreach ($tplData as $field => $value) {
            if (isset($this->{$field})) {
                $this->{$field} = $value;
            }
        }
    }

    protected function showGoodsListByOrder(ICommand $command)
    {
        $this->showCommonGoodsListTpl($command, $this->goodsList);
    }

    protected function showCommonGoodsListTpl(ICommand $command, $goodsList)
    {
        foreach ($goodsList as $item) {
            $goodsName = $item['title'];
            $payPrice = $item['payPrice'];
            $originPrice = $item['originPrice'];
            $lineGoodsNameCount = 10;//名称一行占 10 个汉字
            $lineSpaceWidth = 32;
            $lineGoodsNameSpace = $lineGoodsNameCount * 2;
            $strWidth = mb_strwidth($goodsName, 'utf8');
            for ($i = 0,$line = 1; mb_strlen($subGoodsName = mb_strimwidth($goodsName, $i, $lineGoodsNameSpace, '', 'utf8')) != 0;
                 $i += mb_strlen($subGoodsName), $line++) {

                if ($line == 1) {
                    $goodsDetail = $command->spaceUnAppend($subGoodsName, $lineGoodsNameSpace) . $command->spaceUnAppend("x{$item['num']}", 4, STR_PAD_LEFT) . $command->spaceUnAppend($payPrice, 8, STR_PAD_LEFT);
                    $command->line($goodsDetail);
                    if ($strWidth <= $lineGoodsNameSpace && $item['hasDiscounted']) {
                        $command->line($command->spaceUnAppend("活动前 {$originPrice}", $lineSpaceWidth, STR_PAD_LEFT));
                    }
                } elseif ($line == 2 && $item['hasDiscounted']) {
                    $disCount = $command->spaceUnAppend($subGoodsName, $lineGoodsNameSpace);
                    $disCount .= $command->spaceUnAppend("活动前 {$originPrice}", $lineSpaceWidth - $lineGoodsNameSpace, STR_PAD_LEFT);
                    $command->line($disCount);
                } else {
                    $command->line($command->spaceUnAppend($subGoodsName, $lineSpaceWidth));
                }
            }
        }
    }

    protected function showGoodsListByGroup(ICommand $command)
    {
        foreach ($this->goodsListInGroup as $group => $goodsList) {
            $command->line($command->spaceUnAppend(" {$group} ", 32, STR_PAD_BOTH, "*"))->line();
            $this->showCommonGoodsListTpl($command, $goodsList);
        }
    }
}
