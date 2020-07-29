<?php
/**
 * Created by PhpStorm.
 * User: chenshengkun
 * Date: 2017/8/29
 * Time: 下午2:50
 */

namespace App\Utils\Printer\Bo;

use App\Exceptions\BusinessException;
use App\Utils\Printer\Conf\Printer;


class PrintRecordBo
{
    public  $systemType;
    public  $systemId;
    public  $recordType;
    public  $businessNo;
    public  $printerId;
    public  $content = '';
    public  $isRepeat = false;
    public  $extra = [];
}
