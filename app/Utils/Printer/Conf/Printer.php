<?php
/**
 * Created by PhpStorm.
 * User: chenshengkun
 * Date: 2017/7/6
 * Time: 上午10:37
 */

namespace App\Utils\Printer\Conf;

class Printer
{
    const Auth_WRONG = 4;
    const DISCONNECT = 0;
    const MAX_PRINTER_COUNT_TO_ADD = 10;
    const SUPPORT_TAKEAWAY =  1;
    const SUPPORT_DIANCAN = 2;
    const SUPPORT_CASH = 3;

    const REDIS_PRINTER_ADD_ORDER = "printer.order.cy_printer_order_index";
    const REDIS_PRINTER_RECEIPT_NO = "printer.receipt.cy_printer_receipt_of_today";

    const ON_PRINT = 1;
    const ALREADY_PRINTED = 2;
    const PRINT_FAILED = 3;

    public static $statusMap = [
        0 => "未连接",
        1 => "状态未知",
        2 => "在线",
        3 => "离线",
        4 => "缺纸"
    ];

    public static $supportTypesMap = [
        1 => "外卖订单",
        2 => "堂食订单",
        3 => "扫码买单"
    ];

    public static $orderTypesMap = [
        "takeaway" => 1,
        "diancan" => 2,
        "diancanFinish" => 2,
        "cash" => 3
    ];

    public static $receiptTitles = [
        "takeaway" => [
            "common" => "外卖",
            "eleme" => "饿了么外卖",
            "meituan" => "美团外卖",
            "preOrder" => "预订单-外卖"
        ],
        "diancan" => [
            "common" => "堂食",
            "finish" => "堂食完成",
            "add" => "堂食加菜"
        ],
        "cash" => [
            "common" => "扫码买单"
        ]
    ];

    const SHOW_TYPE_BY_ORDER = 1;
    const SHOW_TYPE_BY_GROUP = 2;

    const ORDER_TYPE_TAKEAWY  = 1;
    const ORDER_TYPE_DIANCANFINISH = 2;
    const ORDER_TYPE_DIANCAN = 2;
    const ORDER_TYPE_CASH = 3;

    const MIN_RECEIPT_NO = 1;
    const NULL_RECEIPT_NO = "-";
}
