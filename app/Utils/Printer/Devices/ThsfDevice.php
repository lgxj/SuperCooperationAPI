<?php
namespace App\Utils\Printer\Devices;
use App\Exceptions\BusinessException;
use App\Utils\Printer\Devices\Command\ICommand;
use App\Utils\Printer\Devices\Command\ThsfCommand;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;


/**
 * Created by PhpStorm.
 * User: suqian
 * Date: 17/10/17
 * Time: 下午7:56
 */
class ThsfDevice extends Device
{

    public function sign(array $param = [])
    {
       return '';
    }

    public function add()
    {
        return ['status'=>true,'msg'=>''];
    }

    public function update()
    {
        return ['status'=>true,'msg'=>''];
    }

    public function remove()
    {
        return ['status'=>true,'msg'=>''];
    }

    public function doPrint($content)
    {
        $config = $this->getRegisterConfig();
        $api = $config['path'].'addOrder';
        $data = array(
            'deviceNo' => $this->deviceNo,
            'key' => $this->deviceKey,
            'printContent'=>$content,
            'times' => $this->printTimes
        );

        try{
            $uri = $config['host'].':'.$config['port'].$api;
            $response =  $this->client->post($uri,[
                'json'=> $data,
                'verify'=>false
            ]);
            $this->response = $response->getBody();
        }catch  (\Exception $e) {
            Log::error('printer_order_exception', ['code' => $e->getCode(), 'msg' => $e->getMessage(), 'trace' => $e->getTraceAsString(), 'data' => $data]);
            throw $e;
        }

        $response = json_decode($this->response,true);
        if (!trim($this->response) || !$response || !isset($response['responseCode'])) {
            Log::error('printer_unexpected_response', ['param' => $data, 'response' => $this->response]);
            throw new BusinessException('打印失败');
        }

        $responseCode = Arr::get($response,'responseCode',16);
        $responseMsg = Arr::get($response,'msg',16);
        $deviceType = $this->getDeviceType();
        if($deviceType == 1 && ($responseCode >=0 && $responseCode <= 3)){
            $ticketId = Arr::get($response,'orderindex','');
            return ['status'=>true,'msg'=>$responseMsg,'ticketId'=>$ticketId];
        }elseif($deviceType == 2 && $responseCode ==0){
            $ticketId = Arr::get($response,'orderindex','');
            return ['status'=>true,'msg'=>$responseMsg,'ticketId'=>$ticketId];
        }
        return ['status'=>false,'msg'=>$responseMsg,'ticketId'=>0];
    }

    public function machineStatus()
    {
        $config = $this->getRegisterConfig();
        $api = $config['path'].'queryPrinterStatus';
        $data = array(
            'deviceNo' => $this->deviceNo,
            'key' => $this->deviceKey
        );
        $uri = $config['host'].':'.$config['port'].$api;
        $response =  $this->client->post($uri,[
            'json'=> $data,
            'verify'=>false
        ]);
        $this->response = $response->getBody();
        $response = json_decode($this->response,true);
        $responseMsg =  Arr::get($response,'msg','');

        if(false !== strstr($responseMsg, "错误")){
            throw new BusinessException($response['msg']);
        }
        if (false !== strstr($responseMsg, "离线") || false !== strstr($responseMsg, "下线") || false !== strstr($responseMsg, "offline")) {
            $return = ["code"=>self::STATUS_OFFLINE,"msg" => "操作成功，请检查打印机的电源和开关"];
        }elseif (false !== strstr($responseMsg, "不正常") || false !== strstr($responseMsg, "缺纸")) {
            $return = ["code"=>self::STATUS_PAPERLESS,"msg" => "操作成功，打印机目前缺纸，请及时补充纸张"];
        }elseif(false !== strstr($responseMsg, "正常在线") || false !== strstr($responseMsg, "状态正常") ||  false !== strstr($responseMsg, "纸张正常")) {
            $return = ["code"=>self::STATUS_ONLINE,"msg" => "操作成功，打印机当前可正常使用"];
        }elseif(false !== strstr($responseMsg, "online")) {
            $return = ["code"=>self::STATUS_ONLINE,"msg" => "操作成功，打印机当前可正常使用"];
        }else{
            $return = ["code"=>self::STATUS_UNKNOWN,"msg" => $responseMsg];
        }
        return $return;
    }

    public function ticketStatus($ticket)
    {
        $config = $this->getRegisterConfig();
        $api = $config['path'].'queryOrder';
        $data = array(
            'deviceNo' => $this->deviceNo,
            'key' => $this->deviceKey,
            'orderindex'=>$ticket
        );
        $uri = $config['host'].':'.$config['port'].$api;
        $response =  $this->client->post($uri,[
            'json'=> $data,
            'verify'=>false
        ]);
        $this->response = $response->getBody();
        $response = json_decode($this->response,true);
        $responseCode = Arr::get($response,'responseCode',16);
        $responseMsg = Arr::get($response,'msg','');

        $deviceType = $this->getDeviceType();
        if($deviceType == 1 && ($responseCode >=0 && $responseCode <= 3)){
            $printStatus = false;
            if(strstr($responseMsg, "完成") || strstr($responseMsg, "打印中")){
                $printStatus = true;
            }
            return ['status'=>true,'msg'=>$responseMsg,'printStatus'=>$printStatus];
        }elseif($deviceType == 2 && $responseCode ==0){
            $printStatus = false;
            if(false !== strstr($responseMsg, "已打印")){
                $printStatus = true;
            }
            return ['status'=>true,'msg'=>$responseMsg,'printStatus'=>$printStatus];
        }
        return ['status'=>false,'msg'=>$responseMsg,'printStatus'=>false];
    }

    public function getDeviceType()
    {
        return (int)(substr(trim($this->deviceNo), 3, 1));
    }

    /**
     * @return ICommand
     */
    public function getCommand()
    {
        return new ThsfCommand($this);
    }
}
