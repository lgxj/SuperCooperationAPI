<?php
namespace App\Bridges\Pool;

use App\Services\Pool\ArticleCategoryService;

class ArticleCategoryBridge extends BasePoolBridge
{
    public function __construct(ArticleCategoryService $articleCategoryService)
    {
        $this->service = $articleCategoryService;
    }
}
