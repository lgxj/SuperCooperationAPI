<?php
/**
 * Created by PhpStorm.
 * User: suqian
 * Date: 17/10/23
 * Time: 下午3:12
 */

namespace App\Utils\Printer\Bo;


use App\Utils\Printer\Bo\TplDataSource\Cash\Cash;
use App\Utils\Printer\Bo\TplDataSource\Diancan\Diancan;
use App\Utils\Printer\Bo\TplDataSource\Diancan\Finish;
use App\Utils\Printer\Bo\TplDataSource\Takeaway\Takeaway;
use App\Utils\Printer\Conf\Printer;

class TplDataSource
{
    protected $kdtId = 0;

    protected $orderNo = '';

    protected $extra = [];

    protected $deviceConfig = [];

    public function __construct($kdtId, $orderNo, array $extra = [],array $deviceConfig = [])
    {
        $this->kdtId = $kdtId;
        $this->orderNo = $orderNo;
        $this->extra = $extra;
        $this->deviceConfig = $deviceConfig;
    }

    public function takeaway()
    {
        $takeaway = new Takeaway();
        $output =  $takeaway->output($this->kdtId, $this->orderNo, $this->extra);
        $output['goodsListInGroup'] =  $this->sortByGroup($this->kdtId, $this->deviceConfig, $output['goodsList']);
        return $output;
    }



    public function cash()
    {
        $cash = new Cash();
        return $cash->output($this->kdtId, $this->orderNo);
    }

    /**
     * @param $kdtId
     * @param $deviceConfig
     * @param $goodsList
     * @return \Generator
     */
    protected function sortByGroup($kdtId, $deviceConfig, $goodsList)
    {
        if(isset($deviceConfig['showType']) && $deviceConfig['showType'] != Printer::SHOW_TYPE_BY_GROUP){
            return [];
        }
        //带分组的商品列表
        $goodsIds = [];
        foreach ($goodsList as $goods) {
            $goodsIds[] = $goods['goodsId'];
        }
        $param = [
            'kdtId' => $kdtId,
            'itemIds' => $goodsIds,
        ];
        $list = [];
        $goodsListContainGroup = [];
        foreach ($list as $item) {
            $goodsListContainGroup[$item['itemId']] = [
                'groupId' => $item['groupId'],
                'groupTitle' => $item['title'],
            ];
        }

        $goodsListInGroup = [];
        foreach ($goodsList as $goods) {
            $groupTitle = isset($goodsListContainGroup[$goods['goodsId']]) ?
                $goodsListContainGroup[$goods['goodsId']]['groupTitle'] : "其他";

            if (!isset($goodsListInGroup[$groupTitle])) {
                $goodsListInGroup[$groupTitle] = array();
            }
            $goodsListInGroup[$groupTitle][] = $goods;
        }

        return $goodsListInGroup;
    }
}
