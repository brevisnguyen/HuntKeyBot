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
 * @property int $admin_id
 * @property bool $is_end
 * @property Carbon|null $work_time
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property User $user
 * @property Chat $chat
 * @property Collection|Deposit[] $deposits
 * @property Collection|Issued[] $issueds
 * @property Collection|Relationship[] $relationships
 *
 * @package App\Models
 */
class Shift extends Model
{
	protected $table = 'shifts';

	protected $casts = [
		'chat_id' => 'int',
		'admin_id' => 'int',
		'is_end' => 'bool'
	];

	protected $dates = [
		'work_time'
	];

	protected $fillable = [
		'chat_id',
		'admin_id',
		'is_end',
		'work_time'
	];

	public function user()
	{
		return $this->belongsTo(User::class, 'admin_id');
	}

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

	public function relationships()
	{
		return $this->hasMany(Relationship::class);
	}
}
