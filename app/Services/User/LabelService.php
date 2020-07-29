<?php


namespace App\Services\User;


use App\Consts\UserConst;
use App\Models\User\Label;
use App\Models\User\UserLabel;

/**
 * 标签服务层
 *
 * Class LabelService
 * @package App\Services\User
 */
class LabelService
{

    public function getRandLabels($labelType = UserConst::LABEL_TYPE_EMPLOYER){
        $labelModel = $this->getLabelModel();
        return $labelModel->inRandomOrder()->where('label_type',$labelType)->limit(10)->get()->toArray();
    }

    public function getLabels($labelType = UserConst::LABEL_TYPE_EMPLOYER){
        $labelModel = $this->getLabelModel();
        return $labelModel->where('label_type',$labelType)->get()->toArray();
    }

    public function getUserHotLabels($userId,$labelType = UserConst::LABEL_TYPE_EMPLOYER,$limit = 10){
        if($userId <= 0){
            return [];
        }
        $userLabelModel = $this->getUserLabelModel();
        $userLabels = $userLabelModel->where(['user_id'=>$userId,'label_type'=>$labelType])->orderByDesc('label_num')->limit($limit)->get();
        if(empty($userLabels)){
            return [];
        }
        $labelIds = $userLabels->pluck('label_id')->toArray();
        $labels = $this->getLabelsByIds($labelIds);
        $list = [];
        $userLabels->each(function ($userLabel) use($labels,&$list){
            $labelId = $userLabel['label_id'];
            $label = $labels[$labelId] ?? [];
            if(empty($label)){
                return ;
            }
            $tmp = $userLabel;
            $tmp['label_name'] = $label;
            $list[] = $tmp;
        });
        return $list;
    }


    public function saveUserLabel($userId,array $userLabel = [],$userType = UserConst::LABEL_TYPE_EMPLOYER){
        if(empty($userLabel) || $userId <= 0){
            return null;
        }
        $labels = $this->getLabelsByNames($userLabel,$userType);
        if(empty($labels)){
            return null;
        }
        $labels->each(function ($name,$id) use($userId,$userType){
            $label = $this->getUserLabelModel();
            $exist = $label->where(['user_id'=>$userId,'label_type'=>$userType,'label_id'=>$id])->first();
           if(!$exist){
               $label->user_id = $userId;
               $label->label_type = $userType;
               $label->label_id = $id;
               $label->label_num = 1;
               $label->save();
           }else{
               $exist->increment('label_num',1);
           }
        });
        return $labels;
    }

    public function getLabelsByNames(array $labels,$userType = UserConst::LABEL_TYPE_EMPLOYER){
        if(empty($labels)){
            return [];
        }
        $labelModel = $this->getLabelModel();
        return $labelModel->whereIn('label_name',$labels)->where('label_type',$userType)->pluck('label_name','label_id');
    }

    public function getLabelsByIds(array $labelIds){
        if(empty($labelIds)){
            return [];
        }
        $labelModel = $this->getLabelModel();
        return $labelModel->whereIn('label_id',$labelIds)->pluck('label_name','label_id');
    }

    public function getUserLabelModel(){
        return new UserLabel();
    }

    public function getLabelModel(){
        return new Label();
    }
}
