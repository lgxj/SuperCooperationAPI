<?php


namespace App\Services\Common;


use App\Exceptions\BusinessException;
use App\Models\Pool\AppVersion;
use App\Services\ScService;

/**
 * app升级管理
 *
 * Class UpgradeService
 * @package App\Services\Common
 */
class UpgradeService extends ScService
{

    public function getLatestVersion($appName,$appType,$version,$userId = 0){
        $upgradeInstallBlack = [];
        if(empty($appName) || empty($appType)){
            return [];
        }
        $update['is_update'] = 0;
        $update['is_gray'] = 0;
        $update['is_force'] = 0;
        $update['is_hot'] = 0;
        $update['is_tip'] = 0;
        $update['download_url'] = '';
        $update['description'] = '';
        $latestVersion = AppVersion::where(['app_name'=>$appName,'app_type'=>$appType])->where('version','>',$version)->orderByDesc('version')->first();
        if(empty($latestVersion)){
            return $update;
        }
        $update = $latestVersion->toArray();
        $update['is_update'] = 1;
        // 非热更新，返回整包下载地址
        if(!$update['is_hot']){
            return $update;
        }
        // 最新的整包更新
        $fullPackage = AppVersion::where(['app_name'=>$appName,'app_type'=>$appType,'is_hot'=>0])->where('version','>',$version)->orderByDesc('version')->first();
        if($fullPackage){
            $fullPackage = $fullPackage->toArray();
            $fullPackage['is_update'] = 1;
            if($fullPackage['is_gray']){
                $fullPackage['is_update'] = in_array($userId,$upgradeInstallBlack) ? 1 : 0;
            }
            return $fullPackage;
        }
        $update['is_hot'] = 1;
        if(!$update['is_force']){
            $must =  AppVersion::where(['app_name'=>$appName,'app_type'=>$appType])->where('version', 'gt', $version)->sum('is_force');
            $update['is_force'] = $must > 0 ? 1 : 0;
        }
        if($update['is_gray']){
            $update['is_update'] = in_array($userId,$upgradeInstallBlack) ? 1 : 0;
        }
        return $update;
    }

    public function getList($filter, $pageSize)
    {
        return AppVersion::when(isset($filter['app_type']), function ($query) use ($filter) {
            $query->where('app_type', $filter['app_type']);
        })->when(isset($filter['is_hot']), function ($query) use ($filter) {
            $query->where('is_hot', $filter['is_hot']);
        })->when(isset($filter['is_force']), function ($query) use ($filter) {
            $query->where('is_force', $filter['is_force']);
        })->when(isset($filter['is_gray']), function ($query) use ($filter) {
            $query->where('is_gray', $filter['is_gray']);
        })->when(isset($filter['keyword']), function ($query) use ($filter) {
            $query->where('version_name', $filter['keyword'])->orWhere('version', $filter['keyword']);
        })->paginate($pageSize);
    }

    public function add($data)
    {
        $version = AppVersion::where('app_type', $data['app_type'])->where('version', $data['version'])->first();
        if ($version) {
            throw new BusinessException('当前平台版本号已存在，请检查');
        }

        $newId = AppVersion::insertGetId($data);
        return ['app_id' => $newId];
    }

    public function edit($id, $data)
    {
        $version = AppVersion::where('app_type', $data['app_type'])->where('version', $data['version'])->where('app_id', '<>', $id)->first();
        if ($version) {
            throw new BusinessException('当前平台版本号已存在，请检查');
        }
        return AppVersion::where('app_id', $id)->update($data) !== false;
    }

    public function del($id)
    {
        return AppVersion::where('app_id', $id)->delete();
    }
}
