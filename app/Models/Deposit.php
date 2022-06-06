<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Deposit
 *
 * @property int $id
 * @property int $user_id
 * @property int $chat_id
 * @property float $amount
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Chat $chat
 * @property User $user
 * @package App\Models
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit query()
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit whereChatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit whereUserId($value)
 * @mixin \Eloquent
 */
class Deposit extends Model
{
	protected $table = 'deposits';

	protected $casts = [
		'user_id' => 'int',
		'chat_id' => 'int',
		'amount' => 'float'
	];

	protected $fillable = [
		'user_id',
		'chat_id',
		'amount'
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
