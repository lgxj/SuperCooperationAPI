<?php


namespace App\Models\User;


class UserFeedback extends BaseUser
{

    protected $table = 'user_feedback';

    protected $primaryKey = 'feedback_id';

    /**
     * 所属用户
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
