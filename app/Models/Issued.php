<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Issued
 *
 * @property int $id
 * @property int $user_id
 * @property int $shift_id
 * @property float $amount
 * @property Carbon|null $created_at
 * @property WorkShift $work_shift
 * @property User $user
 * @package App\Models
 * @method static \Illuminate\Database\Eloquent\Builder|Issued newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Issued newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Issued query()
 * @method static \Illuminate\Database\Eloquent\Builder|Issued whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Issued whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Issued whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Issued whereShiftId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Issued whereUserId($value)
 * @mixin \Eloquent
 */
class Issued extends Model
{
	protected $table = 'issueds';
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
		return $this->belongsTo(User::class, 'user_id', 'id');
	}
}
