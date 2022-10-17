<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class User
 *
 * @property int $id
 * @property string $username
 * @property string|null $first_name
 * @property string|null $last_name
 * @property Collection|Deposit[] $deposits
 * @property Collection|Issued[] $issueds
 * @package App\Models
 * @property-read int|null $deposits_count
 * @property-read int|null $issueds_count
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUsername($value)
 * @mixin \Eloquent
 * @property-read Collection|\App\Models\Chat[] $chats
 * @property-read int|null $chats_count
 */
class User extends Model
{
    use HasFactory;
	/**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';

	/**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [];

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
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
		'username',
		'first_name',
		'last_name'
	];

	public function deposits()
	{
		return $this->hasMany(Deposit::class, 'user_id', 'id');
	}

	public function issueds()
	{
		return $this->hasMany(Issued::class, 'user_id', 'id');
	}

	public function chats()
	{
		return $this->belongsToMany(Chat::class)->withPivot('role');
	}
}
