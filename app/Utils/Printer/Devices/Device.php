<?php
namespace App\Utils\Printer\Devices;
use App\Utils\Printer\Devices\Command\ICommand;
use Illuminate\Support\Facades\Config;
use GuzzleHttp\Client;
/**
 * Created by PhpStorm.
 * User: suqian
 * Date: 17/10/17
 * Time: 下午7:54
 */
abstract class Device
{
    protected $deviceNo='';//机器号
    protected $deviceKey='';//机器密钥
    protected $deviceName='';//机器名称
    protected $sign='';//签名
    protected $debug=0;
    protected $response = '';
    protected $requestTime = 0;
    protected $businessId = 0;
    protected $businessType = 0;
    protected $showType = 1;
    protected $printTimes=1;
    protected $deviceConfig = [];
    protected $client = null;

    const STATUS_NOT_CONNECT= 0;
    const STATUS_UNKNOWN = 1;
    const STATUS_ONLINE = 2;
    const STATUS_OFFLINE = 3;
    const STATUS_PAPERLESS = 4;
    const STATUS_ALREADY_USED = 5;//已被第三方占用

    public function __construct(array $device)
    {
        $this->deviceNo = $device['device_no'];
        $this->deviceKey = $device['device_key'];
        $this->deviceName = $device['device_name'];
        $this->businessId = $device['business_id'];
        $this->businessType = $device['business_type'];
        $this->printTimes = $device['print_times'] ?? 1;
        $this->deviceConfig = $device;
        $this->client = new Client();
    }

    public abstract function sign(array $param = []);

    public abstract function add();

    public abstract function remove();

    public abstract function update();

    public abstract function doPrint($content);

    public abstract function machineStatus();

    public abstract function ticketStatus($ticket);

    /**
     * @return ICommand
     */
    public abstract function getCommand();

    /**
     * 换行
     * @return mixed
     */
    public abstract function getDeviceType();

    public function getPrintTimes(){
        return $this->printTimes;
    }

    public function getShowType()
    {
        return $this->showType;
    }

    public function getConfig(){
        return $this->deviceConfig;
    }

    /**
     * @return  array
     */
    protected  function getRegisterConfig(){
        $device = $this->getDeviceClassName();
        $configPath = "printer.{$device}";
        $config = Config::get($configPath, []);
        $extra = isset($this->deviceConfig['extra']) ? $this->deviceConfig['extra'] : [];
        if($extra) {
            return array_merge($config, $extra);
        }
        return $config;
    }

    protected function getDeviceClassName(){
        $fullPath = get_class($this);
        $classArr = explode("\\",  $fullPath);
        $className = array_pop($classArr);
        $device = str_replace('Device','',$className);
        return strtolower($device);
    }

    protected function getMessageByCode($code){
        $list = [
            self::STATUS_NOT_CONNECT=>'断开',
            self::STATUS_UNKNOWN=>'未知',
            self::STATUS_ONLINE=>'在线',
            self::STATUS_OFFLINE=>'离线',
            self::STATUS_PAPERLESS=>'缺纸'
        ];

        return isset($list[$code]) ? $list[$code] : '';
    }
}
