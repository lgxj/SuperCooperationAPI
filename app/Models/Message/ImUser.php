<?php
namespace App\Models\Message;

use App\Models\User\User;

class ImUser extends BaseMessage
{
    protected $table = 'im_user';

    protected $primaryKey = 'im_id';

    protected $fillable = ['user_id', 'user_type', 'identifier', 'nick', 'gender', 'birthday', 'location', 'self_signature', 'allow_type', 'image', 'msg_settings', 'admin_forbid_type', 'level', 'role', 'state', 'reason', 'no_speaking'];

    /**
     * 所属用户
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
