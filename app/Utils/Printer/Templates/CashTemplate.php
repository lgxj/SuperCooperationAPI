<?php
/**
 * Created by PhpStorm.
 * User: suqian
 * Date: 17/10/18
 * Time: 下午3:49
 */

namespace App\Utils\Printer\Templates;


use App\Utils\Printer\Bo\Traits\CashAttribute;

class CashTemplate extends Template
{
    use CashAttribute;

    protected function template()
    {
        $command = $this->getCommand();
        $command->times();
        $command->centerStrong("{$this->title}#" . $this->receiptNo)->line();
        $command->center($this->shopName)->line();

        //订单号、支付方式、交易时间
        $command->cutLine();
        $command->line('订单号：' . $this->orderNo);
        $command->line('支付方式：' . $this->payWay);
        $command->line('交易时间：' . $this->payTime);
        $command->cutLine();

        $tableTitle = $command->spaceUnAppend('名称', 24) . $command->spaceUnAppend('金额', 8, STR_PAD_LEFT);
        $command->line($tableTitle);
        $command->cutLine();

        $goodsDetail = $command->spaceUnAppend("扫码买单", 24).$command->spaceUnAppend($this->payFee, 8,STR_PAD_LEFT);
        $command->line($goodsDetail);
        $command->cutLine();

        if ($this->discountFee > 0) {
            $command->line('优惠方式：' . $this->discountWay);
            $command->line('优惠金额：' . $this->discountFee);
            $command->line();
        }

        $command->height("实付 {$this->payFee} 元")->line();

        return $command->getTemplate();
    }

    protected function readData()
    {
        $tplData =  $this->dataSource->cash();

        foreach ($tplData as $field => $value) {
            if (isset($this->{$field})) {
                $this->{$field} = $value;
            }
        }
    }
}
