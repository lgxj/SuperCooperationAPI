<?php
namespace App\Utils\Printer\Devices;


use App\Exceptions\BusinessException;
use App\Utils\Printer\Devices\Command\FeieCommand;
use App\Utils\Printer\Devices\Command\ICommand;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;

/**
 * Created by PhpStorm.
 * User: suqian
 * Date: 17/10/17
 * Time: 下午7:56
 */
class FeieDevice extends Device
{

    public function sign(array $param = [])
    {
        $config = $this->getRegisterConfig();
        $this->requestTime = time();
        return strtolower(sha1($config['userName'].$config['apiKey'].$this->requestTime));
    }

    public function add()
    {
        $config = $this->getRegisterConfig();
        $api = $config['path'];
        $sign = $this->sign();

        $printContent = "{$this->deviceNo} # {$this->deviceKey} # {$this->deviceName}";
        $data = array(
            'user'=>$config['userName'],
            'stime'=>$this->requestTime,
            'sig'=>$sign,
            'apiname'=>'Open_printerAddlist',
            'printerContent'=>$printContent
        );
        $uri = $config['host'].':'.$config['port'].$api;
        $response =  $this->client->post($uri,[
            'form_params'=> $data,
            'verify'=>false
        ]);
        $this->response = $response->getBody();
        $response = json_decode($this->response,true);print_r($response);
        $successMachine = Arr::get($response,'data',[]);
        if($response['msg'] != 'ok' || empty($successMachine['ok'])){
            if(isset($successMachine['no']) && $successMachine['no']){
                 $error = explode("#",$successMachine['no'][0]);
                 return ['status'=>false,'msg'=>$error[2]];
            }else{
                 return ['status'=>false,'msg'=>$response['msg']];
            }
        }
        return ['status'=>true,'msg'=>''];
    }

    public function remove()
    {
        $config = $this->getRegisterConfig();
        $api = $config['path'];
        $sign = $this->sign();

        $data = array(
            'user'=>$config['userName'],
            'stime'=>$this->requestTime,
            'sig'=>$sign,
            'apiname'=>'Open_printerDelList',
            'snlist'=>$this->deviceNo
        );
        $uri = $config['host'].':'.$config['port'].$api;
        $response =  $this->client->post($uri,[
            'form_params'=> $data,
            'verify'=>false
        ]);
        $this->response = $response->getBody();
        $response = json_decode($this->response,true);
        $successMachine = Arr::get($response,'data',[]);
        if($response['msg'] != 'ok' || empty($successMachine['ok'])){
            if(isset($successMachine['no']) && $successMachine['no']){
                return ['status'=>false,'msg'=>$successMachine['no'][0]];
            }else{
                return ['status'=>false,'msg'=>$response['msg']];
            }
        }
        return ['status'=>true,'msg'=>''];
    }

    public function update()
    {
        $config = $this->getRegisterConfig();
        $api = $config['path'];
        $sign = $this->sign();

        $data = array(
            'user'=>$config['userName'],
            'stime'=>$this->requestTime,
            'sig'=>$sign,
            'apiname'=>'Open_printerEdit',
            'sn'=>$this->deviceNo,
            'name'=>$this->deviceName
        );
        $uri = $config['host'].':'.$config['port'].$api;
        $response =  $this->client->post($uri,[
            'form_params'=> $data,
            'verify'=>false
        ]);
        $this->response = $response->getBody();
        $response = json_decode($this->response,true);

        if(!$response['data']){
            return ['status'=>false,'msg'=>$data['msg']];
        }
        return ['status'=>true,'msg'=>''];
    }

    public function doPrint($content)
    {
        $config = $this->getRegisterConfig();
        $api = $config['path'];
        $sign = $this->sign();
        $data = array(
            'user'=>$config['userName'],
            'stime'=>$this->requestTime,
            'sig'=>$sign,
            'apiname'=>'Open_printMsg',
            'sn'=>$this->deviceNo,
            'content' => $content,
            'times' => $this->printTimes
        );
        try {
            $uri = $config['host'].':'.$config['port'].$api;
            $response =  $this->client->post($uri,[
                'form_params'=> $data,
                'verify'=>false
            ]);
            $this->response = $response->getBody();
        }catch  (\Exception $e) {
            Log::error('printer_order_exception', ['code' => $e->getCode(), 'msg' => $e->getMessage(), 'trace' => $e->getTraceAsString(), 'data' => $data]);
            throw $e;
        }

        $response = json_decode($this->response,true);
        if (!trim($this->response) || !$response || !isset($response['msg'])) {
            Log::error('printer_unexpected_response', ['param' => $data, 'response' => $this->response]);
            throw new BusinessException("打印失败");
        }

        if(!$response['data'] || $response['msg'] != 'ok'){
            return ['status'=>false,'msg'=>$response['msg'],'ticketId'=>0];
        }
        return ['status'=>true,'msg'=>$response['data'],'ticketId'=>$response['data']];
    }

    public function machineStatus()
    {
        $config = $this->getRegisterConfig();
        $api = $config['path'];
        $sign = $this->sign();
        $data = array(
            'user'=>$config['userName'],
            'stime'=>$this->requestTime,
            'sig'=>$sign,
            'apiname'=>'Open_queryPrinterStatus',
            'sn'=>$this->deviceNo
        );
        $uri = $config['host'].':'.$config['port'].$api;
        $response =  $this->client->post($uri,[
            'form_params'=> $data,
            'verify'=>false
        ]);
        $this->response = $response->getBody();
        $response = json_decode($this->response,true);
        $responseMsg = $response['data'];

        if(!$responseMsg || $response['msg'] != 'ok'){
            throw new BusinessException($response['msg']);
        }

        if (false !== strstr($responseMsg, "离线") || false !== strstr($responseMsg, "下线")) {
            $return = ["code"=>self::STATUS_OFFLINE,"msg" => "操作成功，请检查打印机的电源和开关"];
        }elseif (false !== strstr($responseMsg, "不正常") || false !== strstr($responseMsg, "缺纸")) {
            $return = ["code"=>self::STATUS_PAPERLESS,"msg" => "操作成功，打印机目前缺纸，请及时补充纸张"];
        }elseif(false !== strstr($responseMsg, "状态正常")) {
            $return = ["code"=>self::STATUS_ONLINE,"msg" => "操作成功，打印机当前可正常使用"];
        }else{
            $return = ["code"=>self::STATUS_UNKNOWN,"msg" => $responseMsg];
        }
        return $return;
    }

    public function ticketStatus($ticket)
    {
        $config = $this->getRegisterConfig();
        $api = $config['path'];
        $sign = $this->sign();
        $data = array(
            'user'=>$config['userName'],
            'stime'=>$this->requestTime,
            'sig'=>$sign,
            'apiname'=>'Open_queryOrderState',
            'orderid' => $ticket,
        );
        $uri = $config['host'].':'.$config['port'].$api;
        $response =  $this->client->post($uri,[
            'form_params'=> $data,
            'verify'=>false
        ]);
        $this->response = $response->getBody();
        $response = json_decode($this->response,true);

        if(!$response['data'] || $response['msg'] != 'ok'){
            return  ['status'=>false,'msg'=>$response['msg'],'printStatus'=>false];
        }
        $printStatus = $response['data'] ? true  : false;
        return ['status'=>true,'msg'=>'','printStatus'=> $printStatus];
    }

    public function getDeviceType()
    {
    }

    /**
     * @return ICommand
     */
    public function getCommand()
    {
        return new FeieCommand($this);
    }
}
