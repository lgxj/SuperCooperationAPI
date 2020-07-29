<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\ScModel
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ScModel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ScModel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ScModel query()
 * @mixin \Eloquent
 */
class ScModel extends Model
{
    /**
     * 获取表中的所有字段
     * @return mixed
     */
    public function getTableColumns()
    {
        return $this->getConnection()->getSchemaBuilder()->getColumnListing($this->getTable());
    }
}
