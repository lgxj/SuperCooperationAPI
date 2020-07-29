<?php


namespace App\Web\Controllers\Util;

use App\Web\Controllers\ScController;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RedirectMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Psr\Http\Message\ResponseInterface;

class SpiderAddressController extends ScController
{

    protected $url = 'http://www.stats.gov.cn/tjsj/tjbz/tjyqhdmhcxhfdm/2018/';


    public function city(Request $request){
        $key = $request->input("key");
        if($key != '151E#ww234sd^'){
            return fail();
        }
        set_time_limit(0);
        ignore_user_abort(true);
        ini_set('memory_limit', '600M');
        $client = new Client();
        $provinceList = $this->sendRequest($client,'provincetr',2);
        echo "province complete <br>";
        $allCity = [];
        foreach ($provinceList as $provinceCode=>$provinceCity){
            $cityList = $this->sendRequest($client,'citytr',12,$provinceCode,true);
            foreach ($cityList as $code=>$value){
                $allCity[$code] = $value;
            }
        }
        echo "city complete <br>";
        $allRegion = [];
        foreach ($allCity as $code=>$value){
            $regionList = $this->sendRequest($client,'countytr',12,$code,true);
        }
        echo "region complete <br>";
    }


    public function street(Request $request){
        $key = $request->input("key");
        if($key != '151E#ww234sd^'){
            return fail();
        }
        $db = DB::connection("sc_pool");
        set_time_limit(0);
        ignore_user_abort(true);
        ini_set('memory_limit', '600M');
        $regionList = $db->table('address')->where("level",3)->where('address_id','>=',3241)->pluck('name',"gov_area_id");
        foreach ($regionList as $code=>$value){
            $client = new Client();
            $this->sendRequest($client,'towntr',12,$code,$value,true);
        }
    }




    public function village(Request $request){
        $key = $request->input("key");
        if($key != '151E#ww234sd^'){
            return fail();
        }
        $db = DB::connection("sc_pool");
        set_time_limit(0);
        ignore_user_abort(true);
        ini_set('memory_limit', '600M');
        $start =0;
        $end = 0;
        $streetList = $db->table('address')->where("level",4)->whereBetween('id',[$start,$end])->pluck('name',"gov_area_id");
        foreach ($streetList as $code=>$value){
            $client = new Client();
           // $this->sendRequest($client,'villagetr',12,$code);
        }
        \Log::error("complete village start:{$start} end:{$end}");
    }


    public function gov(Request $request){
        $key = $request->input("key");
        if($key != '151E#ww234sd^'){
            return fail();
        }
        set_time_limit(0);
        ignore_user_abort(true);
        $client = new Client();
        $res = $client->request('GET', $this->url, []);
        $body =  $res->getBody();
        $provinceList = $this->code($body);
        foreach ($provinceList as $provinceCode => $province)
        {
            $this->appendData($provinceCode,0,$province,1);
            $cityUrl = $this->url.$provinceCode;
            $res = $client->request('GET', $cityUrl.'.html', []);
            $body =  $res->getBody();
            $cityList = $this->code($body,'citytr',12);
            foreach ($cityList as $cityCode=>$city){
                $this->appendData($cityCode,$provinceCode,$city,2);
                $regionUrl = $this->url.$provinceCode.'/'.substr($cityCode,0,4);
                $res = $client->request('GET', $regionUrl.'.html', []);
                $body =  $res->getBody();
                $regionList = $this->code($body,'countytr',12);
                if(empty($regionList)){
                    continue;
                }
                foreach ($regionList as $regionCode=>$region){
                    $streetUrl = $this->url . $provinceCode . '/' . substr($regionCode, 2, 2) . '/' . substr($regionCode, 0, 6);
                    try {
                        $this->appendData($regionCode,$cityCode,$region,3);
                        $res = $client->request('GET', $streetUrl . '.html', []);
                        $body = $res->getBody();
                        $streetList = $this->code($body, 'towntr', 12);
                        if (empty($streetList)) {
                            continue;
                        }
                        foreach ($streetList as $streetCode => $street){
                            $this->appendData($streetCode,$regionCode,$street,4);
                            $villageUrl = $this->url . $provinceCode . '/' . substr($streetCode, 2, 2) . '/' . substr($streetCode, 4, 2).'/'.substr($streetCode, 0, 9);
                            continue;
                            $res = $client->request('GET', $villageUrl . '.html', []);
                            $body = $res->getBody();
                            $villageList = $this->code($body,'villagetr',12);
                            if(empty($villageList)){
                                continue;
                            }
                            foreach ($villageList as $villageCode=>$villageName){
                                $this->appendData($villageCode,$streetCode,$villageName,5);
                            }

                        }
                    }catch (GuzzleException $e){
                        echo $e->getMessage()."<br/>";
                        echo $streetUrl."<br/>";
                        \Log::error("address street error url:{$streetUrl} message:{$e->getMessage()}");
                        continue;

                    }

                }
                echo "<br/>";

            }
        }
    }

    protected function mapAddress(array  $address ){
        foreach (array_chunk($address,2) as $chunk)
        {
            list($key,$value) = $chunk;
            $map[$key] = $value;
        }
        return $map;
    }

    protected function code($body,$pos = 'provincetr',$codeLength = 2){
        $data = mb_convert_encoding($body, 'UTF-8', 'GBK');
        //裁头
        $offset = @mb_strpos($data, $pos,2000,'GBK');
        if (!$offset) {
            return [];
        }
        $data = mb_substr($data, $offset,NULL,'GBK');
        // 裁尾
        $offset = mb_strpos($data, '</TABLE>', 200,'GBK');
        $data = mb_substr($data, 0, $offset,'GBK');
        preg_match_all("/\d{{$codeLength}}|(?:[a-zA-Z0-9_]*[\x7f-\xff])+/", $data, $out);
        $areaList = $out[0];
        $areaList = $this->mapAddress($areaList);
        return $areaList;
    }

    protected function strpad($code){
        return str_pad($code,12,0,STR_PAD_RIGHT );
    }

    protected function appendData($code,$parentCode,$name,$level,$isCheck =false){
        $db = DB::connection("sc_pool");
        $code = $this->strpad($code);
        if($parentCode > 0) {
            $parentCode = $this->strpad($parentCode);
        }
        if($isCheck){
           $exist = $db->table('address')->where('gov_area_id',$code)->first();
           if($exist){
               \Log::error("exist name {$exist->name} code:{$exist->gov_area_id}");
               return;
           }
        }
        $data = ['gov_area_id'=>$code,'parent_id'=>$parentCode,'name'=>$name,'level'=>$level];
        $db->table('address')->insert($data);
    }

    protected function appendData2($code,$parentCode,$name,$level,&$data = []){
        $code = $this->strpad($code);
        if($parentCode > 0) {
            $parentCode = $this->strpad($parentCode);
        }
        $data[] = ['gov_area_id'=>$code,'parent_id'=>$parentCode,'name'=>$name,'level'=>$level];
    }

    protected function sendRequest(Client $client,$type= 'provincetr',$pos = 2,$code='',$value='',$hasCheck=false){
        $notFound = [
            130101000000,130201000000,130301000000,130401000000,130501000000,130601000000,130701000000,130801000000,130901000000,131001000000,131101000000,140101000000,140201000000,140301000000,140401000000,140501000000,140601000000,140701000000,140801000000,140901000000,141001000000,141101000000,150101000000,150201000000,450301000000,450201000000,
            150301000000,150401000000,150501000000,150601000000,150701000000,150801000000,150901000000,210101000000,210201000000,210301000000,220601000000,220701000000,220801000000,230101000000,230201000000,230301000000,230401000000,230501000000,231201000000,320101000000,320201000000,320301000000,320401000000,231101000000,231001000000,450101000000,
            210401000000,210501000000,210601000000,210701000000,210801000000,210901000000,211001000000,211101000000,211201000000,211301000000,211401000000,220101000000,220201000000,220301000000,220401000000,220501000000,230601000000,230701000000,230801000000,230901000000,320501000000,320601000000,320701000000,320801000000,320901000000,321001000000,
            321101000000,321201000000,321301000000,330101000000,330201000000,330301000000,330401000000,330501000000,330601000000,330701000000,330801000000,330901000000,331001000000,331101000000,340101000000,340201000000,340301000000,340401000000,340501000000,340601000000,340701000000,340801000000,341001000000,341101000000,341201000000,341301000000,
            341501000000,341601000000,341701000000,341801000000,350101000000,350201000000,350301000000,350401000000,350401000000,350501000000,450101000000,350601000000,350701000000,350801000000,350901000000,360101000000,360201000000,360301000000,360401000000,360501000000,360601000000,360701000000,360801000000,360901000000,361001000000,361101000000,
            370101000000,370201000000,370301000000,370401000000,370501000000,370601000000,370701000000,370801000000,370901000000,371001000000,371101000000,371201000000,371301000000,371401000000,371501000000,371601000000,371701000000,410101000000,410201000000,410301000000,410401000000,410501000000,410601000000,410701000000,410801000000,410901000000,
            411001000000,411101000000,411201000000,411301000000,411401000000,411501000000,411601000000,411701000000,420101000000,420201000000,420301000000,420501000000,420601000000,420701000000,420801000000,445301000000,445201000000,421001000000,421101000000,421201000000,421301000000,430101000000,430201000000,430301000000,430401000000,430501000000,
            430601000000,430701000000,430801000000,430901000000,431001000000,431101000000,431201000000,431301000000,440101000000,440201000000,440301000000,440401000000,440501000000,440601000000,440701000000,440801000000,440901000000,441201000000,441301000000,441401000000,441501000000,441601000000,441701000000,441801000000,445101000000,420901000000,
            350527000000,450401000000,450501000000,450601000000,450701000000,450801000000,450901000000,451001000000,451101000000,451201000000,451301000000,451401000000,460101000000,460201000000,510101000000,510301000000,510401000000,510501000000,510601000000,510701000000,510801000000,510901000000,511001000000,511101000000,511301000000,511401000000,
            511501000000,511601000000,511701000000,511801000000,511901000000,512001000000,520101000000,520301000000,520401000000,520501000000,520601000000,530101000000,530301000000,530401000000,530501000000,530601000000,530701000000,530801000000,530901000000,540101000000,540501000000,610101000000,610201000000,610301000000,610401000000,610482000000,
            610501000000,610601000000,610701000000,610801000000,610901000000,611001000000,620101000000,620301000000,620401000000,620401000000,620501000000,620601000000,620701000000,620801000000,620901000000,621001000000,621101000000,621201000000,630101000000,640101000000,640201000000,640301000000,640401000000,640501000000,650101000000,650201000000,


        ];
        if(in_array($code,$notFound)){
            return [];
        }
        $res = null;
        if($type == 'provincetr'){
            $level = 1;
            $parentCode = 0;
            $uri = $this->url;
        }elseif($type == 'citytr'){
            $level = 2;
            $parentCode = substr($code, 0, 2);
            $uri = $this->url.substr($code,0,2).'.html';
        }elseif($type == 'countytr'){
            $level = 3;
            $parentCode = substr($code, 0, 4);
            $uri = $this->url.substr($code,0,2).'/'.substr($code,0,4).'.html';
        }elseif($type == 'towntr'){
            $level = 4;
            $parentCode = substr($code, 0, 6);
            $uri = $this->url . substr($code,0,2) . '/' . substr($code, 2, 2) . '/' . substr($code, 0, 6).'.html';

        }elseif($type == 'villagetr'){
            $level = 5;
            $parentCode = substr($code, 0, 9);
            $uri = $this->url . substr($code,0,2) . '/' . substr($code, 2, 2) . '/' . substr($code, 4, 2).'/'.substr($code, 0, 9).'.html';
        }
        try {
            $res = $client->request('GET', $uri, []);
        }catch (GuzzleException $e) {
            echo $e->getMessage() . "\n";
            echo $e->getMessage() . "<br/>";
            \Log::error("address {$type} error url:{$uri} message:{$e->getMessage()} code:{$e->getCode()} b_code:{$code} address:{$value}");
            try {
                if ($e->getCode() == '502' || $e->getCode() == '504') {
                    \Log::error("{$uri} retry");
                    $res = $client->request('GET', $uri, []);
                }
            }catch (GuzzleException $e){
                \Log::error("address2 {$type} error url:{$uri} message:{$e->getMessage()} code:{$e->getCode()} b_code:{$code} address:{$value}");
                return $e->getCode();
            }
        }
        if(empty($res)){
            return [];
        }
        $body = $res->getBody();
        $areaList = $this->code($body, $type, $pos);
        if (empty($areaList)) {
            return [];
        }
        foreach ($areaList as $areaCode=>$areaName){
            $this->appendData($areaCode,$parentCode,$areaName,$level,$hasCheck);
        }
        return $areaList;
    }
}
