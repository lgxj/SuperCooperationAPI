<?php
namespace App\Models;

/**
 * Eloquent树形数据
 * @package App\Http\Models
 */
trait EloquentTree
{
    /**
     * 子级
     * @return mixed
     */
    public function child()
    {
        return $this->hasMany(static::class, self::$pid_name ?? 'fid', self::$staticPrimaryKey)
            ->when(self::$child_where ?? false, function ($query) {
                $query->where(self::$child_where);
            })
            ->when(self::$child_with, function ($query) {
                $query->with(self::$child_with);
            })
            ->when(self::$child_has, function ($query) {
                $query->whereHas(self::$child_has[0], self::$child_has[1] ?? null);
            })
            ->select(self::$child_fields);
    }

    /**
     * 所有子级
     * @return mixed
     */
    public function children()
    {
        return $this->child()->with(__FUNCTION__);
    }

    /**
     * 父级
     * @return mixed
     */
    public function parent()
    {
        return $this->belongsTo(static::class, self::$pid_name ?? 'fid');
    }

    /**
     * 所有父级
     * @return mixed
     */
    public function allParent()
    {
        return $this->parent()->with(__FUNCTION__);
    }

    /**
     * 设置下级查询字段
     * @param $columns
     */
    public function setChildrenField($columns)
    {
        self::$child_fields = $columns;
    }

    /**
     * 设置下级查询条件
     * @param $where
     */
    public function setChildrenWhere($where)
    {
        self::$child_where = $where;
    }

    /**
     * 设置下级关联查询
     * @param $with
     */
    public function setChildrenWith($with)
    {
        self::$child_with = $with;
    }

    /**
     * 设置下级关联条件
     * @param $has
     */
    public function setChildrenHas($has)
    {
        self::$child_has = $has;
    }
}
