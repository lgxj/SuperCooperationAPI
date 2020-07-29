<?php


namespace App\Services\Pool;


use App\Exceptions\BusinessException;
use App\Models\Pool\Printer;
use App\Models\Pool\PrinterRecord;
use App\Utils\Printer\Bo\PrinterBo;
use App\Utils\Printer\Bo\PrintRecordBo;
use App\Utils\Printer\Common\Help;

/**
 * wifi打印机管理
 *
 * Class PrinterService
 * @package App\Services\Pool
 */
class PrinterService
{

    public function addDevice(PrinterBo $printerBo){
        $businessId = $printerBo->businessId;
        $businessType = $printerBo->businessType;
        $deviceNo = $printerBo->deviceNo;
        $printerType = $printerBo->printerType;

        $help = new Help();
        if($businessId <= 0 || empty($deviceNo)){
            throw new BusinessException("参数错误");
        }


        $deviceConfig =  $this->getByDeviceNo($businessId,$businessType,$deviceNo);
        if($deviceConfig){
            throw new BusinessException("设备号已绑定");
        }

        $count =  $this->countByBusinessId($businessId,$businessType);print_r($count);
        if ($count >= 10) {
            throw new BusinessException("添加打印机已超上限");
        }
        $printer = new Printer();
        $printer->business_id = $businessId;
        $printer->business_type = $businessType;
        $printer->printer_type = $printerType;
        $printer->device_name = $printerBo->deviceName;
        $printer->device_no = $deviceNo;
        $printer->device_key = $printerBo->deviceKey;
        $printer->status = 1;
        $device = $help->getDeviceByTypeCode($printerType, $printer->toArray());
        $response =  $device->add();
        if(!$response['status']){
            throw new BusinessException($response['msg']);
        }
        $status =  $device->machineStatus();
        $printer->device_status = $status['code'];
        $printer->save();
        return $status;
    }

    public function editDevice(PrinterBo $printerBo){
        $businessId = $printerBo->businessId;
        $businessType = $printerBo->businessType;
        $deviceNo = $printerBo->deviceNo;
        $printerType = $printerBo->printerType;
        $printerId = $printerBo->printerId;
        $help = new Help();
        if($businessId <= 0 || $printerId <= 0){
            throw new BusinessException('参数错误');
        }
        $deviceConfig = $this->getByPrinterId($businessId,$businessType,$printerId);
        if(empty($deviceConfig)){
            throw new BusinessException("打印机不存在");
        }
        $deviceConfig->device_name = $printerBo->deviceName;
        $device = $help->getDeviceByTypeCode($printerType,$deviceConfig->toArray());
        $response =  $device->update();
        if(!$response['status']){
            throw new BusinessException($response['msg']);
        }
        $status =  $device->machineStatus();
        $deviceConfig->device_status = $status['code'];
        $deviceConfig->status = $printerBo->status;
        $deviceConfig->save();
        return $status;
    }

    public function get(int $businessId,int $businessType,int $printerId){
        if($businessId <= 0 || $printerId <= 0){
            return [];
        }
        $deviceConfig = $this->getByPrinterId($businessId,$businessType,$printerId);
        return  $deviceConfig ? $deviceConfig->toArray() : [];
    }

    public function doPrint(PrintRecordBo $printRecordBo){
        $content = $printRecordBo->content;
        $businessNo = $printRecordBo->businessNo;
        $isRepeat = $printRecordBo->isRepeat;
        $businessId = $printRecordBo->systemId;
        $businessType = $printRecordBo->systemType;
        $printerId = $printRecordBo->printerId;

        $help = new Help();
        if($businessId <= 0 || $printerId <= 0 || empty($content)){
            throw new BusinessException("参数错误");
        }

        $deviceConfig = $this->getByPrinterId($businessId,$businessType,$printerId);
        if(empty($deviceConfig)){
            throw new BusinessException("打印机不存在");
        }

        $printRecord = $this->getTicketByTargetNo($businessNo);
        if($printRecord && !$isRepeat){
            return ['status'=>true,'msg'=>'打印成功','ticketId'=>$printRecord['receiptNo']];
        }

        $device = $help->getDeviceByTypeCode($deviceConfig['printer_type'],$deviceConfig->toArray());
        $return =  $device->doPrint($content);

        if($return['status'] && $return['ticketId'] && empty($printRecord)){
            $record = new PrinterRecord();
            $record->business_no = $businessNo;
            $record->record_type = 0;
            $record->system_type = $businessType;
            $record->system_id = $businessId;
            $record->return_receipt_no = $return['ticketId'];
            $record->printer_type = $deviceConfig['printer_type'];
            $record->print_status = 1;
            $record->printer_id = $printerId;
            $record->save();
        }
        return $return;
    }

    public function connect(int $businessId,int $businessType,int $printerId){
        if($businessId <= 0 || $printerId <= 0){
            throw new BusinessException('参数错误');
        }
        $help = new Help();
        $deviceConfig = $this->getByPrinterId($businessId,$businessType,$printerId);
        if(empty($deviceConfig)){
            throw new BusinessException('没有此打印机');
        }
        $device = $help->getDeviceByTypeCode($deviceConfig['printer_type'],$deviceConfig->toArray());
        $status =  $device->machineStatus();
        $deviceConfig->device_status = $status['code'];//后面要改成1。兼容
        $deviceConfig->status = $status['code'];
        $deviceConfig->save();
        return $status;
    }

    public function disConnect(int $businessId,int $businessType,int $printerId){
        if($businessId <= 0 || $printerId <= 0){
            throw new BusinessException('参数错误');
        }
        $deviceConfig = $this->getByPrinterId($businessId,$businessType,$printerId);
        if(empty($deviceConfig)){
            throw new BusinessException('没有此打印机');
        }
        $deviceConfig->status = 0;
        $deviceConfig->save();
        return  ["msg" => "断开成功"];
    }

    public function remove(int $businessId,int $businessType,int $printerId){
        if($businessId <= 0 || $printerId <= 0){
            throw new BusinessException('参数错误');
        }
        $deviceConfig = $this->getByPrinterId($businessId,$businessType,$printerId);
        if(empty($deviceConfig)){
            throw new BusinessException('没有此打印机');
        }
        $help = new Help();
        $device = $help->getDeviceByTypeCode($deviceConfig['printer_type'],$deviceConfig->toArray());
        $response =  $device->remove();
        if(!$response['status']){
            throw new BusinessException($response['msg']);
        }
        return $deviceConfig->delete();
    }

    public function status(int $businessId,int $businessType,int $printerId){
        if($businessId <= 0 || $printerId <= 0){
            throw new BusinessException('参数错误');
        }
        $deviceConfig = $this->getByPrinterId($businessId,$businessType,$printerId);
        if(empty($deviceConfig)){
            throw new BusinessException('没有此打印机');
        }
        $help = new Help();
        $device = $help->getDeviceByTypeCode($deviceConfig['printer_type'],$deviceConfig->toArray());
        $status =  $device->machineStatus();
        $deviceConfig->device_status = $status['code'];
        $deviceConfig->save();
        return $status;
    }

    public function ticketStatus(int $businessId,int $businessType,int $printerId, $ticketId){
        $help = new Help();
        if($businessId <= 0 || $printerId <= 0 || empty($ticketId)){
            throw new BusinessException('参数错误');
        }
        $deviceConfig = $this->getByPrinterId($businessId,$businessType,$printerId);
        if(empty($deviceConfig)){
            throw new BusinessException('没有此打印机');
        }
        $device = $help->getDeviceByTypeCode($deviceConfig['printer_type'],$deviceConfig->toArray());
        $status =  $device->ticketStatus($ticketId);
        return $status;
    }

    public function listByBusinessId(int $businessId,int $businessType){
        return Printer::getModel()->where(['business_id'=>$businessId,'business_type'=>$businessType])->get()->toArray();
    }

    protected function getByDeviceNo(int $businessId,int $businessType,string $deviceNo){
        if($businessId <= 0 || empty($deviceNo)){
            return null;
        }
        return Printer::getModel()->where(['business_id'=>$businessId,'business_type'=>$businessType,'device_no'=>$deviceNo])->first();
    }

    protected function getByPrinterId(int $businessId,int $businessType,int $printerId){
        if($businessId <= 0 || $printerId <= 0){
            return null;
        }
        return Printer::getModel()->where(['business_id'=>$businessId,'business_type'=>$businessType,'printer_id'=>$printerId])->first();
    }

    protected function countByBusinessId(int $businessId,int $businessType){
        if($businessId <= 0 || empty($deviceNo)){
            return 0;
        }
        return Printer::getModel()->where(['business_id'=>$businessId,'business_type'=>$businessType])->count();
    }

    public function getTicketByTargetNo($printerId,$businessNo){
        if($printerId <= 0 || $businessNo <= 0){
            return null;
        }
        return PrinterRecord::getModel()->where(['printer_id'=>$printerId,'business_no'=>$businessNo])->first();
    }
}
