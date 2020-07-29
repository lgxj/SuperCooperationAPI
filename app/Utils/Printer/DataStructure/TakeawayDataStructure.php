<?php
/**
 * Created by PhpStorm.
 * User: suqian
 * Date: 17/12/4
 * Time: 下午2:29
 */
namespace App\Utils\Printer\DataStructure;


use App\Utils\Printer\Bo\Traits\TakeawayAttribute;
use App\Utils\Printer\Conf\Printer;

class TakeawayDataStructure  extends DataStructure
{
    use TakeawayAttribute;

    protected function readData()
    {
        $tplData =  $this->dataSource->takeaway();
        foreach ($tplData as $field => $value) {
            if (isset($this->{$field})) {
                $this->{$field} = $value;
            }
        }
    }

    protected function build()
    {
        $this->row("{$this->title}#{$this->receiptNo}",TYPE_TEXT,ALIGN_CENTER,SIZE_HEIGHT,FACE_STRONG,WEIGHT_NORMAL);
        $this->row($this->shopName,TYPE_TEXT,ALIGN_CENTER,SIZE_NORMAL,FACE_NORMAL,WEIGHT_NORMAL);
        if ($this->expectReceiveTime) {
            $this->cutLine();
            $this->row("预定 {$this->expectReceiveTime} 送达",TYPE_TEXT,ALIGN_CENTER,SIZE_NORMAL,FACE_STRONG,WEIGHT_NORMAL);
        }
        $this->cutLine();
        $this->row('订单号：'.$this->orderNo,TYPE_TEXT,ALIGN_LEFT,SIZE_NORMAL,FACE_NORMAL,WEIGHT_NORMAL);
        $this->row('支付方式：'.$this->payment,TYPE_TEXT,ALIGN_LEFT,SIZE_NORMAL,FACE_NORMAL,WEIGHT_NORMAL);
        $this->row('交易时间：'.$this->payTime,TYPE_TEXT,ALIGN_LEFT,SIZE_NORMAL,FACE_NORMAL,WEIGHT_NORMAL);
        $this->cutLine();

        $this->listRow('商品名称',TYPE_TEXT,ALIGN_LEFT,SIZE_NORMAL,FACE_NORMAL,WEIGHT_FIVE);
        $this->col("数量",TYPE_TEXT,ALIGN_CENTER,SIZE_NORMAL,FACE_NORMAL,WEIGHT_TWO);
        $this->col("金额",TYPE_TEXT,ALIGN_RIGHT,SIZE_NORMAL,FACE_NORMAL,WEIGHT_TWO);
        $this->cutLine();

        $showType = $this->getShowType();
        if ($showType == Printer::SHOW_TYPE_BY_ORDER) {
            $this->showGoodsListByOrder();
        } elseif ($showType == Printer::SHOW_TYPE_BY_GROUP) {
            $this->showGoodsListByGroup();
        }
        if($this->boxPrice > 0) {
            $this->listRow('餐盒费', TYPE_TEXT, ALIGN_LEFT, SIZE_NORMAL, FACE_NORMAL, WEIGHT_FIVE);
            $this->col($this->boxPrice, TYPE_TEXT, ALIGN_RIGHT, SIZE_NORMAL, FACE_NORMAL, WEIGHT_FOUR);
        }

        $this->listRow('配送费', TYPE_TEXT, ALIGN_LEFT, SIZE_NORMAL, FACE_NORMAL, WEIGHT_FIVE);
        $this->col($this->postFee, TYPE_TEXT, ALIGN_RIGHT, SIZE_NORMAL, FACE_NORMAL, WEIGHT_FOUR);

        $this->cutLine();
        $this->row('订单原价：'.$this->totalOriginPrice,TYPE_TEXT,ALIGN_LEFT,SIZE_NORMAL,FACE_NORMAL,WEIGHT_NORMAL);
        $this->cutLine();

        if($this->discountFee > 0) {
            $this->row('优惠方式：' . $this->discountWay, TYPE_TEXT, ALIGN_LEFT, SIZE_NORMAL, FACE_NORMAL, WEIGHT_NORMAL);
            $this->row('优惠金额：' . $this->discountFee, TYPE_TEXT, ALIGN_LEFT, SIZE_NORMAL, FACE_NORMAL, WEIGHT_NORMAL);
        }

        $this->spaceLine();
        $this->row("共 {$this->totalNum} 件，实付 {$this->payFee} 元",TYPE_TEXT,ALIGN_LEFT,SIZE_NORMAL,FACE_STRONG,WEIGHT_NORMAL);
        $this->cutLine();

        if ($this->buyerMemo || $this->sellerMemo) {
            if($this->buyerMemo){
                $this->row("买家留言：{$this->buyerMemo}",TYPE_TEXT,ALIGN_LEFT,SIZE_HEIGHT,FACE_STRONG,WEIGHT_NORMAL);
            }
            if($this->sellerMemo){
                $this->row("商家备注：{$this->sellerMemo}",TYPE_TEXT,ALIGN_LEFT,SIZE_HEIGHT,FACE_STRONG,WEIGHT_NORMAL);
            }
            $this->cutLine();
        }

        if ($this->invoiceTitle) {
            if($this->taxNo) {
                $this->invoiceTitle = $this->invoiceTitle . ' | ' . $this->taxNo;
            }
            $this->row("发票信息：{$this->invoiceTitle}",TYPE_TEXT,ALIGN_LEFT,SIZE_NORMAL,FACE_NORMAL,WEIGHT_NORMAL);
            $this->cutLine();
        }

        $this->row($this->receiverName,TYPE_TEXT,ALIGN_LEFT,SIZE_HEIGHT,FACE_STRONG,WEIGHT_NORMAL);
        $this->row($this->receiverPhone,TYPE_TEXT,ALIGN_LEFT,SIZE_HEIGHT,FACE_STRONG,WEIGHT_NORMAL);
        $this->row($this->receiverAddress,TYPE_TEXT,ALIGN_LEFT,SIZE_HEIGHT,FACE_STRONG,WEIGHT_NORMAL);
        $this->spaceLine();
        $this->spaceLine();
        $this->spaceLine();


    }

    private function showGoodsListByOrder(){
        $this->showCommonGoodsListTpl($this->goodsList);
    }

    private function showGoodsListByGroup(){
        foreach ($this->goodsListInGroup as $group => $goodsList) {
            $groupText = $this->spaceFull(" {$group} ",STR_PAD_BOTH,'*');
            $this->row($groupText,TYPE_TEXT,ALIGN_NORMAL,SIZE_NORMAL,FACE_NORMAL,WEIGHT_NORMAL);
            $this->showCommonGoodsListTpl($goodsList);
        }
    }

    private function showCommonGoodsListTpl(array $goodsList){
        foreach ($goodsList as $item) {
            $this->listRow($item['title'],TYPE_TEXT,ALIGN_LEFT,SIZE_NORMAL,FACE_NORMAL,WEIGHT_FIVE);
            $this->col($item['num'],TYPE_TEXT,ALIGN_CENTER,SIZE_NORMAL,FACE_NORMAL,WEIGHT_TWO);
            $this->col($item['payPrice'],TYPE_TEXT,ALIGN_RIGHT,SIZE_NORMAL,FACE_NORMAL,WEIGHT_TWO);
            if($item['hasDiscounted']){
                $this->row("活动前 {$item['originPrice']}",TYPE_TEXT,ALIGN_RIGHT,SIZE_NORMAL,FACE_NORMAL,WEIGHT_NORMAL);
            }
        }
    }
}
