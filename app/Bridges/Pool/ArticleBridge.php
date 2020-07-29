<?php
namespace App\Bridges\Pool;

use App\Services\Pool\ArticleService;

class ArticleBridge extends BasePoolBridge
{
    public function __construct(ArticleService $articleService)
    {
        $this->service = $articleService;
    }
}
