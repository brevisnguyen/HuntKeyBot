<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class WorkShift
 *
 * @property int $id
 * @property int $chat_id
 * @property bool $is_start
 * @property bool $is_end
 * @property Carbon|null $start_time
 * @property Carbon|null $stop_time
 * @property Chat $chat
 * @property Collection|Deposit[] $deposits
 * @property Collection|Issued[] $issueds
 * @package App\Models
 * @property-read int|null $deposits_count
 * @property-read int|null $issueds_count
 * @method static \Illuminate\Database\Eloquent\Builder|WorkShift newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WorkShift newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WorkShift query()
 * @method static \Illuminate\Database\Eloquent\Builder|WorkShift whereChatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WorkShift whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WorkShift whereIsEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WorkShift whereIsStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WorkShift whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WorkShift whereStopTime($value)
 * @mixin \Eloquent
 */
class WorkShift extends Model
{
	protected $table = 'work_shifts';
	public $timestamps = false;

	protected $casts = [
		'chat_id' => 'int',
		'is_start' => 'bool',
		'is_end' => 'bool'
	];

	protected $dates = [
		'start_time',
		'stop_time'
	];

	protected $fillable = [
		'chat_id',
		'is_start',
		'is_end',
		'start_time',
		'stop_time'
	];

	public function chat()
	{
		return $this->belongsTo(Chat::class);
	}

	public function deposits()
	{
		return $this->hasMany(Deposit::class, 'shift_id');
	}

	public function issueds()
	{
		return $this->hasMany(Issued::class, 'shift_id');
	}
}
