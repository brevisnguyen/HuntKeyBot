<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class User
 * 
 * @property int $id
 * @property bool|null $is_bot
 * @property string $username
 * @property string $first_name
 * @property string $last_name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|Deposit[] $deposits
 * @property Collection|Issued[] $issueds
 * @property Collection|Relationship[] $relationships
 * @property Collection|Shift[] $shifts
 *
 * @package App\Models
 */
class User extends Model
{
	protected $table = 'users';
	public $incrementing = false;

	protected $casts = [
		'id' => 'int',
		'is_bot' => 'bool'
	];

	protected $fillable = [
		'id',
		'is_bot',
		'username',
		'first_name',
		'last_name'
	];

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
		return $this->hasMany(Relationship::class, 'username', 'username');
	}

	public function shifts()
	{
		return $this->hasMany(Shift::class, 'admin_id');
	}
}
