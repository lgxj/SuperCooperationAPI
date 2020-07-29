<?php
/**
 * Created by PhpStorm.
 * User: suqian
 * Date: 17/12/1
 * Time: 上午10:22
 */

namespace App\Utils\Printer\DataStructure;

use App\Utils\Printer\Bo\Traits\CashAttribute;

class CashDataStructure extends DataStructure
{

    use CashAttribute;

    protected function readData()
    {
        $tplData =  $this->dataSource->cash();

        foreach ($tplData as $field => $value) {
            if (isset($this->{$field})) {
                $this->{$field} = $value;
            }
        }
    }

    protected function build()
    {
        $this->row("{$this->title}#{$this->receiptNo}",TYPE_TEXT,ALIGN_CENTER,SIZE_NORMAL,FACE_STRONG,WEIGHT_NORMAL);
        $this->row($this->shopName,TYPE_TEXT,ALIGN_CENTER,SIZE_NORMAL,FACE_NORMAL,WEIGHT_NORMAL);

        $this->cutLine();
        $this->row("订单号：{$this->orderNo}",TYPE_TEXT,ALIGN_LEFT,SIZE_NORMAL,FACE_NORMAL,WEIGHT_NORMAL);
        $this->row("支付方式：{$this->payWay}",TYPE_TEXT,ALIGN_LEFT,SIZE_NORMAL,FACE_NORMAL,WEIGHT_NORMAL);
        $this->row("交易时间：{$this->payTime}",TYPE_TEXT,ALIGN_LEFT,SIZE_NORMAL,FACE_NORMAL,WEIGHT_NORMAL);
        $this->cutLine();

        $this->listRow('名称',TYPE_TEXT,ALIGN_LEFT,SIZE_NORMAL,FACE_NORMAL,WEIGHT_SIX);
        $this->col('金额',TYPE_TEXT,ALIGN_RIGHT,SIZE_NORMAL,FACE_NORMAL,WEIGHT_THREE);
        $this->cutLine();
        $this->listRow('"扫码买单"',TYPE_TEXT,ALIGN_LEFT,SIZE_NORMAL,FACE_NORMAL,WEIGHT_SIX);
        $this->col($this->payFee,TYPE_TEXT,ALIGN_RIGHT,SIZE_NORMAL,FACE_NORMAL,WEIGHT_THREE);
        $this->cutLine();

        if ($this->discountFee > 0) {
            $this->row("优惠方式：{$this->discountWay}", TYPE_TEXT, ALIGN_LEFT, SIZE_NORMAL, FACE_NORMAL, WEIGHT_NORMAL);
            $this->row("优惠金额：{$this->discountFee}", TYPE_TEXT, ALIGN_LEFT, SIZE_NORMAL, FACE_NORMAL, WEIGHT_NORMAL);
            $this->spaceLine();
        }

        $this->row("实付 {$this->payFee} 元",TYPE_TEXT,ALIGN_LEFT,SIZE_NORMAL,FACE_STRONG,WEIGHT_NORMAL);
        $this->spaceLine();
        $this->spaceLine();
        $this->spaceLine();

    }
}
