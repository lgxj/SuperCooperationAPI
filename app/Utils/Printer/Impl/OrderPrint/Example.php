<?php
use App\Utils\Printer\Bo\PrintRecordBo;

$device = [
    'device_no' => '930514893',
    'device_key' => 'wqxv7tcn',
    'device_name' => '打印机',
    'business_id' => 1,
    'business_type' => 1
];


$printerService = new \App\Services\Pool\PrinterService();
$printerBo = new \App\Utils\Printer\Bo\PrinterBo();
$printerBo->deviceName = "打印机";
$printerBo->deviceKey = 'wqxv7tcn';
$printerBo->deviceNo = '930514893';
$printerBo->businessId = 1;
$printerBo->businessType = 1;
$printerBo->printerType = 0;
// $printerService->addDevice($printerBo);

$cash = new \App\Utils\Printer\Impl\OrderPrint\Cash();
$printerRecordBo = new  PrintRecordBo();
$printerRecordBo->systemId = 1;
$printerRecordBo->systemType = 1;
$printerRecordBo->businessNo = '201812314141';
$printerRecordBo->printerId = 1;
$cash->execute($printerRecordBo);

print_r($printerService->status(1,1,1));
