<?php
namespace App\Models\Pool;

use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 文章
 * @package App\Models\Pool
 */
class Article extends BasePool
{

//    use SoftDeletes;

    protected $table = 'article';

    protected $primaryKey = 'article_id';

    protected $casts = [
        'tag' => 'array',
        'photos' => 'array'
    ];

    protected $fillable = ['category_id', 'title', 'author', 'tag', 'content_type', 'link', 'cover', 'photos', 'summary', 'content', 'sort'];

    /**
     * 所属分类
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(ArticleCategory::class, 'category_id', 'article_category_id');
    }
}
