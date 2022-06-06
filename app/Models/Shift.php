<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Shift
 *
 * @property int $id
 * @property int $chat_id
 * @property bool $is_start
 * @property bool $is_end
 * @property Carbon|null $work_time
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Chat $chat
 * @property Collection|Deposit[] $deposits
 * @property Collection|Issued[] $issueds
 * @package App\Models
 * @property-read int|null $deposits_count
 * @property-read int|null $issueds_count
 * @method static \Illuminate\Database\Eloquent\Builder|Shift newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Shift newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Shift query()
 * @method static \Illuminate\Database\Eloquent\Builder|Shift whereChatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shift whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shift whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shift whereIsEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shift whereIsStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shift whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shift whereWorkTime($value)
 * @mixin \Eloquent
 */
class Shift extends Model
{
	protected $table = 'shifts';

	protected $casts = [
		'chat_id' => 'int',
		'is_start' => 'bool',
		'is_end' => 'bool'
	];

	protected $dates = [
		'work_time'
	];

	protected $fillable = [
		'chat_id',
		'is_start',
		'is_end',
		'work_time'
	];

	public function chat()
	{
		return $this->belongsTo(Chat::class);
	}

	public function deposits()
	{
		return $this->hasMany(Deposit::class);
	}

	public function issueds()
	{
		return $this->hasMany(Issued::class);
	}
}
