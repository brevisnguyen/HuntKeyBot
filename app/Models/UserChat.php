<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class UserChat
 *
 * @property int $id
 * @property int $chat_id
 * @property string $username
 * @property string $role
 * @property Carbon|null $created_at
 * @property Chat $chat
 * @package App\Models
 * @method static \Illuminate\Database\Eloquent\Builder|UserChat newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserChat newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserChat query()
 * @method static \Illuminate\Database\Eloquent\Builder|UserChat whereChatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserChat whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserChat whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserChat whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserChat whereUsername($value)
 * @mixin \Eloquent
 */
class UserChat extends Model
{
	protected $table = 'user_chats';
	public $timestamps = false;

	protected $casts = [
		'chat_id' => 'int'
	];

	protected $fillable = [
		'chat_id',
		'username',
		'role'
	];

	public function chat()
	{
		return $this->belongsTo(Chat::class);
	}
}
