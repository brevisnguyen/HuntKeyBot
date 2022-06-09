<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Chat
 *
 * @property int $id
 * @property string $type
 * @property string|null $title
 * @property string|null $username
 * @property Collection|UserChat[] $user_chats
 * @property Collection|WorkShift[] $work_shifts
 * @package App\Models
 * @property-read int|null $user_chats_count
 * @property-read int|null $work_shifts_count
 * @method static \Illuminate\Database\Eloquent\Builder|Chat newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Chat newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Chat query()
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereUsername($value)
 * @mixin \Eloquent
 */
class Chat extends Model
{
	protected $table = 'chats';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'id' => 'int'
	];

	protected $fillable = [
		'id',
		'type',
		'title',
		'username'
	];

	public function users()
	{
		return $this->belongsToMany(User::class, 'user_chats', 'chat_id', 'username');
	}

	public function work_shifts()
	{
		return $this->hasMany(WorkShift::class);
	}

	public function deposits()
	{
		return $this->hasManyThrough(Deposit::class, WorkShift::class, 'chat_id', 'shift_id');
	}

	public function issueds()
	{
		return $this->hasManyThrough(Issued::class, WorkShift::class, 'chat_id', 'shift_id');
	}
}
