<?php


namespace App\Utils;


class Recursion
{


    static public function getParentList($arr, $id,$autoincrementIdName = 'id'){
        //$arr 所有分类列表
        //$id 父级分类id
        static $list = [];
        foreach ($arr as $u) {
            if ($u[$autoincrementIdName] == $id) {//父级分类id等于所查找的id
                $list[] = $u;
                if ($u['parent_id'] > 0) {
                    self::getParentList($arr, $u['parent_id']);
                }
            }
        }
        return $list;
    }

    static public function recursionTree($address,$autoincrementIdName = 'id')
    {
        $tree = [];
        foreach ($address as $item) {
            if (isset($address[$item['parent_id']])) {
                $parentItem = $address[$item['parent_id']];
                $address[$item['parent_id']]['sub'][$item['name']] = &$address[$autoincrementIdName];
            } else {
                $tree[$item['name']] = &$address[$item[$autoincrementIdName]];
            }
        }
        return $tree;

    }

    static public function stripFieldRecursion(&$data, $field = 'parent_id')
    {
        foreach ($data as $key => &$item) {

            if (isset($item[$field])) {
                unset($data[$key][$field]);
                if (isset($item['sub'])) {
                    self::stripFieldRecursion($item['sub'], $field);
                }
            }
        }
    }
}
