<?php


namespace App\Models\Trade\Order;


use App\Models\Trade\BaseTrade;

class Category extends BaseTrade
{
    protected $table = 'order_category';

    protected $primaryKey = 'category_id';

    protected $fillable = ['category_id', 'category_name', 'sort'];
}
