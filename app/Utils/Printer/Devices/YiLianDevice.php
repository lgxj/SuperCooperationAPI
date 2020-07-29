<?php
namespace App\Utils\Printer\Devices;


use App\Exceptions\BusinessException;
use App\Utils\Printer\Devices\Command\ICommand;
use App\Utils\Printer\Devices\Command\YiLianCommand;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;
/**
 * Created by PhpStorm.
 * User: suqian
 * Date: 17/10/17
 * Time: 下午7:57
 */
class YiLianDevice extends Device
{
    private $isPush = false;

    public function sign(array $param = [])
    {
        $config = $this->getRegisterConfig();
        $this->requestTime = time();
        if($this->isPush){
            return strtoupper(md5($config['apiKey'].$this->requestTime));
        }

        $param['partner'] = $config['userId'];
        $param['machine_code'] = $this->deviceNo;

        ksort($param);
        $stringToBeSigned = $config['apiKey'];
        foreach ($param as $k => $v) {
            $stringToBeSigned .= urldecode($k.$v);
        }
        $stringToBeSigned .= $this->deviceKey;
        return strtoupper(md5($stringToBeSigned));
    }

    public function add()
    {
        $config = $this->getRegisterConfig();

        $api = $config['path'].'addprint.php';

        $param = [
            'printname' => $this->deviceName,
            'username' => $config['userName'],
            'mobilephone' => ''
        ];
        $sign = $this->sign($param);
        $data = array(
            'partner'=>$config['userId'],
            'msign'=>$this->deviceKey,
            'machine_code'=>$this->deviceNo,
            'sign'=>$sign
        );
        $data = array_merge($data,$param);
        $uri = $config['host'].':'.$config['port'].$api;
        $response =  $this->client->post($uri,[
            'json'=> $data,
            'verify'=>false
        ]);
        $this->response = $response->getBody();
        $response = json_decode($this->response,true);
        if(!empty($response['error'])){
            throw new BusinessException($response['error']);
        }
        $responseCode = intval($response);

        $codeData = [
            1=>'添加成功',
            2=>'该打印机已被授权占用，补全打印机配置即可使用',
            3=>'添加失败',
            4=>'添加失败',
            5=>'用户验证失败',
            6=>'非法终端号'
        ];

        if ($responseCode == 2) {
            throw new BusinessException("该打印机已被授权占用哦");
        }
        if($responseCode != 1){
            $msg = isset($codeData[$responseCode]) ? $codeData[$responseCode] : '未知错误';
            return ['status'=>false ,'msg'=>$msg];
        }
        return ['status'=>true,'msg'=>'添加成功'];
    }

    public function update()
    {
        $config = $this->getRegisterConfig();
        $api = $config['path'].'changeprint.php';

        $param = [
            'printname'=>$this->deviceName,
            'mobilephone'=>''
        ];
        $sign = $this->sign($param);
        $data = array(
            'partner'=>$config['userId'],
            'machine_code'=>$this->deviceNo,
            'sign'=>$sign
        );
        $data = array_merge($data,$param);
        $uri = $config['host'].':'.$config['port'].$api;
        $response =  $this->client->post($uri,[
            'json'=> $data,
            'verify'=>false
        ]);        $this->response = $response->getBody();
        $response = json_decode($this->response,true);
        $responseCode = intval(Arr::get($response,'errno',''));
        $responseError = Arr::get($response,'error','');

        $codeData = [
            40001=>'非post请求，禁止访问',
            40002=>'缺少参数',
            40003=>'签名错误',
            40004=>'内部错误',
            20000=>'修改成功'
        ];
        if($responseCode != 20000){
            $msg = isset($codeData[$responseCode]) ? $codeData[$responseCode] : '';
            return ['status'=>false,'msg'=>$msg ? $msg : $responseError];
        }
        return ['status'=>true,'msg'=>''];
    }

    public function remove()
    {
        $config = $this->getRegisterConfig();
        $api = $config['path'].'removeprint.php';

        $sign = $this->sign();
        $data = array(
            'partner'=>$config['userId'],
            'machine_code'=>$this->deviceNo,
            'sign'=>$sign
        );
        $uri = $config['host'].':'.$config['port'].$api;
        $response =  $this->client->post($uri,[
            'json'=> $data,
            'verify'=>false
        ]);
        $this->response = $response->getBody();
        $response = json_decode($this->response,true);
        $responseCode = intval($response);

        $codeData = [
            1=>'删除成功',
            2=>'打印机不存在',
            3=>'删除失败',
            4=>'认证失败'
        ];
        if($responseCode > 3){
            $msg = isset($codeData[$responseCode]) ? $codeData[$responseCode] : '未知错误';
            return ['status'=>false,'msg'=>$msg];
        }
        return ['status'=>true,'msg'=>''];
    }

    public function doPrint($content)
    {
        $config = $this->getRegisterConfig();
        $api = $config['path'];

        $param['time'] = time();
        $sign = $this->sign($param);
        $data = array(
            'partner'=>$config['userId'],
            'machine_code'=>$this->deviceNo,
            'content' => $content,
            'sign'=>$sign
        );
        $data = array_merge($data,$param);
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
        if (!trim($this->response) || !$response || !isset($response['state'])) {
            Log::error('printer_unexpected_response', ['param' => $data, 'response' => $this->response]);
            throw new BusinessException("打印失败");
        }

        $responseCode = intval($response['state']);
        $codeData = [
            1=>'成功',
            2=>'提交时间超时。验证你所提交的时间戳超过3分钟后拒绝接受',
            3=>'参数有误',
            4=>'sign加密验证失败'
        ];
        if($responseCode != 1){
            $msg = isset($codeData[$responseCode]) ? $codeData[$responseCode] : '未知错误';
            return ['status'=>false,'msg'=>$msg,'ticketId'=>0];
        }
        return ['status'=>true,'msg'=>'打印成功','ticketId'=>$response['id']];
    }

    public function machineStatus()
    {
        $config = $this->getRegisterConfig();
        $api = $config['path'].'getstatus.php';

        $sign = $this->sign();
        $data = array(
            'partner'=>$config['userId'],
            'machine_code'=>$this->deviceNo,
            'sign'=>$sign
        );
        $uri = $config['host'].':'.$config['port'].$api;
        $response =  $this->client->post($uri,[
            'json'=> $data,
            'verify'=>false
        ]);        $this->response = $response->getBody();
        if(strpos($this->response,':') > 0) {
            $response = explode(':', $this->response);
            $responseCode = intval($response[1]);
        }else{
            $responseCode = intval($this->response);
        }

        if(count($response) != 2){
            throw new BusinessException($response['msg']);
        }

        if (0 == $responseCode) {
            $return = ["code"=>self::STATUS_OFFLINE,"msg" => "操作成功，请检查打印机的电源和开关"];
        }elseif (2 == $responseCode) {
            $return = ["code"=>self::STATUS_PAPERLESS,"msg" => "操作成功，打印机目前缺纸，请及时补充纸张"];
        }elseif(1 == $responseCode) {
            $return = ["code"=>self::STATUS_ONLINE,"msg" => "操作成功，打印机当前可正常使用"];
        }else{
            $return = ["code"=>self::STATUS_UNKNOWN,"msg" => '参数填写错误'];
        }
        return $return;
    }

    public function ticketStatus($ticket)
    {
        $config = $this->getRegisterConfig();
        $api = $config['path'].'getorderstatus.php';

        $param['order_id'] = $ticket;
        $sign = $this->sign($param);
        $data = array(
            'partner'=>$config['userId'],
            'machine_code'=>$this->deviceNo,
            'sign'=>$sign,
            'order_id'=>$ticket
        );
        $uri = $config['host'].':'.$config['port'].$api;
        $response =  $this->client->post($uri,[
            'json'=> $data,
            'verify'=>false
        ]);
        $this->response = $response->getBody();
        $response = json_decode($this->response,true);
        $responseCode = intval(Arr::get($response,'errno',''));
        $responseError = Arr::get($response,'error','');

        $codeData = [
            40001=>'非post请求，禁止访问',
            40002=>'缺少参数',
            40003=>'签名错误',
            40004=>'内部错误',
            40005=>'小票ID错误',
            20000=>'修改成功'
        ];
        if($responseCode != 20000){
            $msg = isset($codeData[$responseCode]) ? $codeData[$responseCode] : '';
            return ['status'=>false,'printStatus'=>false,'msg'=>$msg ? $msg : $responseError];
        }
        $print = Arr::get($response,'data',[]);
        $printStatus = false;
        if(false !== strstr($print['status'], "已打印")){
            $printStatus = true;
        }

        return ['status'=>true,'msg'=>'','printStatus'=>$printStatus];
    }

    public function getDeviceType()
    {
        $config = $this->getRegisterConfig();
        $api = $config['path'].'getversion.php';

        $sign = $this->sign();
        $data = array(
            'partner'=>$config['userId'],
            'machine_code'=>$this->deviceNo,
            'sign'=>$sign
        );
        $uri = $config['host'].':'.$config['port'].$api;
        $response =  $this->client->post($uri,[
            'json'=> $data,
            'verify'=>false
        ]);
        $this->response = $response->getBody();
        $response = json_decode($this->response,true);
        $responseCode = intval($response['errno']);
        $responseError = Arr::get($response,'error','');
        $data = Arr::get($response,'data',[]);

        $codeData = [
            40001=>'非post请求，禁止访问',
            40002=>'缺少参数',
            40003=>'签名错误',
            40004=>'内部错误',
            20000=>'修改成功'
        ];
        if($responseCode != 20000){
            $msg = isset($codeData[$responseCode]) ? $codeData[$responseCode] : $responseError;
            throw new BusinessException($msg,$responseCode);
        }
        return isset($data['version']) ? $data['version'] : '';
    }

    /**
     * @return ICommand
     */
    public function getCommand()
    {
        return new YiLianCommand($this);
    }
}
