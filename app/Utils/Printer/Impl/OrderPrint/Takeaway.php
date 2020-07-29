<?php
/**
 * Created by PhpStorm.
 * User: suqian
 * Date: 17/10/23
 * Time: 下午4:01
 */

namespace App\Utils\Printer\Impl\OrderPrint;



use App\Models\Pool\PrinterRecord;
use App\Services\Pool\PrinterService;
use App\Utils\Printer\Bo\PrintRecordBo;
use App\Utils\Printer\Common\Help;
use App\Utils\Printer\Templates\TakeawayTemplate;

class Takeaway
{
    public function execute(PrintRecordBo $printRecordBo)
    {
        $extra = $printRecordBo->extra ? $printRecordBo->extra : [];
        $content = $printRecordBo->content;
        $businessNo = $printRecordBo->businessNo;
        $isRepeat = $printRecordBo->isRepeat;
        $businessId = $printRecordBo->systemId;
        $businessType = $printRecordBo->systemType;
        $printerId = $printRecordBo->printerId;

        $help = new Help();
        $printerService = new PrinterService();
        $deviceConfig = $printerService->get($businessId,$businessType,$printerId);
        if(empty($deviceConfig)){
            return [];
        }

        $ticketRecord =  $printerService->getTicketByTargetNo($businessNo,$printerId);
        if($ticketRecord && !$isRepeat){
            return ['status'=>true,'msg'=>'订单已打印','ticketId'=>$ticketRecord['receiptNo']];
        }

        $device = $help->getDeviceByTypeCode($deviceConfig['printer_type'],$deviceConfig);
        $command = $device->getCommand();
        $template = new TakeawayTemplate($command,$businessId,$businessNo,$extra);
        $content =  $template->render();
        $return = $device->doPrint($content);

        if($return['status'] && $return['ticketId']){
            if(empty($ticketRecord)) {
                $record = new PrinterRecord();
                $record->business_no = $businessNo;
                $record->record_type = 2;
                $record->system_type = $businessType;
                $record->system_id = $businessId;
                $record->return_receipt_no = $return['ticketId'];
                $record->printer_type = $deviceConfig['printer_type'];
                $record->print_status = 1;
                $record->printer_id = $printerId;
                $record->save();
            }
        }
        return $return;
    }
}
