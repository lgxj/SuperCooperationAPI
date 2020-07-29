<?php
namespace App\Models\Pool;

/**
 * 文章分类
 * @package App\Models\Pool
 */
class ArticleCategory extends BasePool
{
    protected $table = 'article_category';

    protected $primaryKey = 'article_category_id';

    protected $casts = [
        'list_fields' => 'array',
        'detail_fields' => 'array'
    ];

    protected $fillable = ['name', 'list_type', 'cover_size', 'list_fields', 'detail_fields', 'photo_size'];

    /**
     * 关联文章
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function articles()
    {
        return $this->hasMany(Article::class, 'category_id', 'article_category_id');
    }
}
