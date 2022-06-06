<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Chat
 * 
 * @property int $id
 * @property string $type
 * @property string|null $title
 * @property string|null $username
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|Shift[] $shifts
 *
 * @package App\Models
 */
class Chat extends Model
{
	protected $table = 'chats';
	public $incrementing = false;

	protected $casts = [
		'id' => 'int'
	];

	protected $fillable = [
		'id',
		'type',
		'title',
		'username'
	];

	public function shifts()
	{
		return $this->hasMany(Shift::class);
	}
}
