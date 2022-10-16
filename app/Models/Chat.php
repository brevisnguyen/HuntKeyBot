<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Chat
 *
 * @property int $id
 * @property string $type
 * @property string|null $title
 * @property string|null $username
 * @property Collection|UserChat[] $user_chats
 * @property Collection|Shift[] $shifts
 * @package App\Models
 * @property-read int|null $user_chats_count
 * @property-read int|null $shifts_count
 * @method static \Illuminate\Database\Eloquent\Builder|Chat newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Chat newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Chat query()
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereUsername($value)
 * @mixin \Eloquent
 * @property-read Collection|\App\Models\Deposit[] $deposits
 * @property-read int|null $deposits_count
 * @property-read Collection|\App\Models\Issued[] $issueds
 * @property-read int|null $issueds_count
 * @property-read Collection|\App\Models\User[] $users
 * @property-read int|null $users_count
 */
class Chat extends Model
{
	/**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'chats';

	/**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
	public $incrementing = false;

	/**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

	/**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'int',
    ];

	/**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
	protected $fillable = [
		'id',
		'type',
		'title',
		'username'
	];

	public function users()
	{
		return $this->belongsToMany(User::class)->withPivot('role');
	}

	public function shifts()
	{
		return $this->hasMany(Shift::class);
	}

	public function deposits()
	{
		return $this->hasManyThrough(Deposit::class, WorkShift::class, 'chat_id', 'shift_id');
	}

	public function issueds()
	{
		return $this->hasManyThrough(Issued::class, WorkShift::class, 'chat_id', 'shift_id');
	}
}
