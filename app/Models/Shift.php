<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Shift
 *
 * @property int $id
 * @property int $chat_id
 * @property int $user_id
 * @property bool $is_end
 * @property bool $is_admin
 * @property bool $is_operator
 * @property Carbon|null $work_time
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Chat $chat
 * @property User $user
 * @package App\Models
 * @method static \Illuminate\Database\Eloquent\Builder|Shift newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Shift newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Shift query()
 * @method static \Illuminate\Database\Eloquent\Builder|Shift whereChatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shift whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shift whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shift whereIsAdmin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shift whereIsEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shift whereIsOperator($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shift whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shift whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shift whereWorkTime($value)
 * @mixin \Eloquent
 */
class Shift extends Model
{
	protected $table = 'shifts';

	protected $casts = [
		'chat_id' => 'int',
		'user_id' => 'int',
		'is_end' => 'bool',
		'is_admin' => 'bool',
		'is_operator' => 'bool'
	];

	protected $dates = [
		'work_time'
	];

	protected $fillable = [
		'chat_id',
		'user_id',
		'is_end',
		'is_admin',
		'is_operator',
		'work_time'
	];

	public function chat()
	{
		return $this->belongsTo(Chat::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
