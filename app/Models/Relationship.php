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
 * @property int $chat_id
 * @property string $username
 * @property string $role
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Chat $chat
 * @package App\Models
 * @method static \Illuminate\Database\Eloquent\Builder|Relationship newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Relationship newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Relationship query()
 * @method static \Illuminate\Database\Eloquent\Builder|Relationship whereChatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Relationship whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Relationship whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Relationship whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Relationship whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Relationship whereUsername($value)
 * @mixin \Eloquent
 */
class Relationship extends Model
{
	protected $table = 'relationships';

	protected $casts = [
		'chat_id' => 'int'
	];

	protected $fillable = [
		'chat_id',
		'username',
		'role'
	];

	public function chat()
	{
		return $this->belongsTo(Chat::class);
	}
}
