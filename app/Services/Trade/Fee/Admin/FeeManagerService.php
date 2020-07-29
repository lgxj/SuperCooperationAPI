<?php


namespace App\Services\Trade\Fee\Admin;


use App\Consts\Trade\FeeConst;
use App\Consts\Trade\PayConst;
use App\Exceptions\BusinessException;
use App\Models\Trade\Fee\FeeRule;
use App\Services\ScService;

/**
 * 平台扣费选项服务层
 * Class FeeManagerService
 * @package App\Services\Trade\Fund\Admin
 */
class FeeManagerService extends ScService
{

    public function search($filter = [], $pageSize = 10, $orderColumn = 'created_at', $direction = 'desc')
    {
        $list = FeeRule::orderBy($orderColumn, $direction)->paginate($pageSize);
        if (empty($list)) {
            return [];
        }
        collect($list->items())->map(function ($item) {
            $item['fee_type'] = FeeConst::getTypeList($item['fee_type']);
            $item['biz_source'] = PayConst::getBizSourceList($item['biz_source']);
            $item['channel'] = PayConst::getChannelList($item['channel']);
            $item['display_state'] = FeeConst::getStateList($item['state']);
            return $item;
        });
        return $list;
    }

    /**
     * 编辑
     * @param $id
     * @param array $data
     * @return bool
     * @throws BusinessException
     */
    public function edit($id, $data = []) {
        $validate = \Validator::make($data, [
            'ratio' => 'required'
        ], [
            'ratio.required' => '扣费比例不能为空'
        ]);

        if ($validate->fails()) {
            throw new BusinessException($validate->errors()->first());
        }

        $feerule = FeeRule::find($id)->first();
        
        if (!$feerule) {
            throw new BusinessException('信息不存在');
        }

        try {
            // 保存
            FeeRule::where('fee_rule_id', $id)->update($data);

            return true;
        } catch (\Exception $e) {
            \Log::error('编辑收费管理失败:' . json_encode($data, 320) . PHP_EOL . ' message: ' . $e->getMessage());
            throw new BusinessException('编辑失败');
        }
    }
}
