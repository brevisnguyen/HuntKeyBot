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
 * @property int $shift_id
 * @property float $amount
 * @property Carbon|null $created_at
 * @property WorkShift $work_shift
 * @property User $user
 * @package App\Models
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit query()
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit whereShiftId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit whereUserId($value)
 * @mixin \Eloquent
 */
class Deposit extends Model
{
	protected $table = 'deposits';
	public $timestamps = false;

	protected $casts = [
		'user_id' => 'int',
		'shift_id' => 'int',
		'amount' => 'float'
	];

	protected $fillable = [
		'user_id',
		'shift_id',
		'amount'
	];

	public function work_shift()
	{
		return $this->belongsTo(WorkShift::class, 'shift_id');
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
