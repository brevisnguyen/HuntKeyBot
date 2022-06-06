<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Relationship
 * 
 * @property int $id
 * @property int $shift_id
 * @property string $username
 * @property string $role
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Shift $shift
 * @property User $user
 *
 * @package App\Models
 */
class Relationship extends Model
{
	protected $table = 'relationships';

	protected $casts = [
		'shift_id' => 'int'
	];

	protected $fillable = [
		'shift_id',
		'username',
		'role'
	];

	public function shift()
	{
		return $this->belongsTo(Shift::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class, 'username', 'username');
	}
}
