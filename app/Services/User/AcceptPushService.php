<?php


namespace App\Services\User;


use App\Consts\DBConnection;
use App\Consts\GlobalConst;
use App\Consts\Trade\OrderConst;
use App\Models\User\User;
use App\Models\User\UserAcceptConfig;
use App\Models\User\UserAcceptConfigCategory;
use App\Services\Pool\YunTuService;
use App\Services\ScService;
use App\Utils\Map\YunTu;

class AcceptPushService extends ScService
{

    public function saveConfig(array $config){

    }


    /**
     * 搜索配置,先过搜索附合任务的条件推送
     *
     * @param int $taskPayPrice
     * @param float $employerLevel
     * @param int $distance
     * @param int $taskStartHour
     * @param int $limit
     * @param int $maxId
     * @return array
     */
    public function searchConfig(int $taskPayPrice = 0,float $employerLevel = 0,int $distance = 0,$taskStartHour = 0,$limit = 100,&$maxId=0){
        $model = UserAcceptConfig::when($taskPayPrice > 0,function ($query) use($taskPayPrice){
            $query->where('employer_price','>=',$taskPayPrice);
        })->when($employerLevel > 0,function ($query) use ($employerLevel){
            $query->where('employer_level','>=',$employerLevel);
        })->when($taskStartHour > 0,function ($query) use ($taskStartHour){
            $query->where('start_at','<=',$taskStartHour);
            $query->where('end_at','>=',$taskStartHour);
        })->when($distance > 0,function ($query) use ($distance){
            $query->where('employer_distance','>=',$distance);
            $query->where('lng','!=','');
        });
        if($maxId <= 0) {
            $maxId = UserAcceptConfig::max('config_id');
        }
        if($maxId <= GlobalConst::RAND_DB_SWITCH){
            $model->inRandomOrder();
        }else{
            $minId = UserAcceptConfig::min('config_id');
            $randStartUserId = rand_db_id($maxId,$minId,$limit);
            $model->where('config_id','>=',$randStartUserId);
        }
        $userIds = $model->select('user_id')->limit($limit)->pluck('user_id')->toArray();
        return $userIds;
    }

    /**
     * 过滤任务分类
     *
     * @param array $userIds
     * @param int $filterCategoryId
     * @return array
     */
    public function filterCategory(array $userIds,int $filterCategoryId){
        if(empty($userIds)){
            return [];
        }
        $userCategoryList = UserAcceptConfigCategory::select(['user_id','category_id'])->whereIn('user_id',$userIds)->get()->groupBy('user_id')->toArray();
        if(empty($userCategoryList)){
            return $userIds;//没有查到数据，是默认所有服务
        }
        $returnUserIds = [];
        foreach ($userIds as $userId){
            if(!isset($userCategoryList[$userId])){
                $returnUserIds[] = $userId; //没有设置，默认所有
                continue;
            }
            $singleUserList = $userCategoryList[$userId];
            $categoryIds = collect($singleUserList)->pluck('category_id')->toArray();
            if(in_array($filterCategoryId,$categoryIds)){
                $returnUserIds[] = $userId;
            }
        }
        return $returnUserIds;
    }

    /**
     * 过滤用户
     *
     * @param array $userIds
     * @param int $taskHelperLevel 任务需要的雇主级别
     * @return array
     */
    public function filterUser(array $userIds,$taskHelperLevel){
        if(empty($userIds)){
            return [];
        }
        $users = User::whereIn('user_id',$userIds)->select(['helper_level','user_status','user_id'])->get()->keyBy('user_id');
        $returnUserId = [];
        foreach ($userIds as $userId){
            $user = $users[$userId] ?? [];
            if(empty($user)){
                continue;
            }
            if($user['helper_level'] < $taskHelperLevel){
                continue;//过滤雇主要求的级别
            }
            if(!$user['user_status']){
                continue;
            }
            $returnUserId[] = $userId;
        }
        return $returnUserId;

    }

    /**
     * 过滤距离
     *
     * @param array $userIds
     * @param $lng
     * @param $lat
     * @param bool $hasDistance 是否一定需要定位距离
     * @return array
     */
    public function filterDistance(array $userIds,$lng,$lat,$hasDistance = true){
        if(empty($userIds)){
            return [];
        }
        if(empty($lng) || empty($lat)){
            return $userIds;
        }
        $configs = $this->getUserService()->getAcceptConfigByUserIds($userIds);
        $returnUserIds = [];
        foreach ($userIds as $userId){
            $config = $configs[$userId] ?? [];
            if(empty($config['lng']) && $hasDistance){
                continue;//没有定位表示没法判定距离
            }
            if(empty($config)){
                $returnUserIds[$userId] = 0;//没有配置表示没有设限
                continue;
            }
            if($config['employer_distance'] <= 0){
                $returnUserIds[$userId] = 0;//配置为0也表示没有设限
                continue;
            }
            $diff_distance = 0;
            if($config['lng']) {
                $diff_distance = distance($lat, $lng, $config['lat'], $config['lng'], 'm');
            }
            if($diff_distance <= $config['employer_distance']){
                $returnUserIds[$userId] = $diff_distance;
            }
        }
        return $returnUserIds;
    }

    public function filterBlackList(int $userId,array $judgeUserIds){
        $returnUserIds = [];
        if(empty($judgeUserIds)){
            return $returnUserIds;
        }
        $blackUserIds = $this->getUserService()->judgeUserBlackList($userId,$judgeUserIds);
        foreach ($judgeUserIds as $userId){
            if(!in_array($userId,$blackUserIds)){
                $returnUserIds[] = $userId;
            }
        }
        return $returnUserIds;
    }




    public function getNearByUsersWithDb($latitude,$longitude,$distance = GlobalConst::NEAR_BY_USER){
        //不随机，附近10公里有1000人就算不错了，前期先数据库
        $userConnection = DBConnection::getUserConnection();
        $sql = "select user_id,ROUND(6378.138*2*ASIN(SQRT(POW(SIN(({$latitude}*PI()/180-lat*PI()/180)/2),2)+COS({$latitude}*PI()/180)*COS(lat*PI()/180)*POW(SIN(({$longitude}*PI()/180-lng*PI()/180)/2),2)))*1000) AS distance FROM user_address_position having distance <= {$distance} limit 1000";
        $users = $userConnection->select($sql);
        $return = [];
        foreach ($users as $user){
            $return[$user->user_id] = $user->distance;
        }
        return $users;
    }

    /**
     * @param $longitude
     * @param $latitude
     * @param int $page
     * @param int $distance
     * @return array
     * @throws \App\Exceptions\BusinessException
     */
    public function getNearByUsersWithYunTu($longitude,$latitude,$page=1,$distance = GlobalConst::NEAR_BY_USER){
        $yunTuUtil = getYunTu();
        $yunTuService = new YunTuService();
        $tableId = $yunTuService->getHelperTableId();
        list($list, $count) = $yunTuUtil->aroundSearch($tableId, $longitude, $latitude, $distance, '', '', '', $page, 10);
        $list = collect($list);
        $userIds = $list->pluck('business_no')->toArray();
        $list = $list->keyBy('business_no')->toArray();
        return [$list,$count];
    }

    public function getUserService(){
        return new UserService();
    }

    protected function getAcceptConfigModel(){
        return new UserAcceptConfig();
    }
}
