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
 * @property Carbon|null $updated_at
 * @property Shift $shift
 * @property User $user
 * @package App\Models
 * @method static \Illuminate\Database\Eloquent\Builder|Issued newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Issued newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Issued query()
 * @method static \Illuminate\Database\Eloquent\Builder|Issued whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Issued whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Issued whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Issued whereShiftId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Issued whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Issued whereUserId($value)
 * @mixin \Eloquent
 */
class Issued extends Model
{
	protected $table = 'issueds';

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

	public function shift()
	{
		return $this->belongsTo(Shift::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
