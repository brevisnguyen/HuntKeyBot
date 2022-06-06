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
 * @property Collection|Relationship[] $relationships
 * @property Collection|Shift[] $shifts
 * @package App\Models
 * @property-read int|null $relationships_count
 * @property-read int|null $shifts_count
 * @method static \Illuminate\Database\Eloquent\Builder|Chat newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Chat newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Chat query()
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereUsername($value)
 * @mixin \Eloquent
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

	public function relationships()
	{
		return $this->hasMany(Relationship::class);
	}

	public function shifts()
	{
		return $this->hasMany(Shift::class);
	}
}
