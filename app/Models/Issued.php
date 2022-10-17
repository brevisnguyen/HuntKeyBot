<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class Issued
 *
 * @property int $id
 * @property int $user_id
 * @property int $shift_id
 * @property float $amount
 * @property Carbon|null $created_at
 * @property WorkShift $work_shift
 * @property User $user
 * @package App\Models
 * @method static \Illuminate\Database\Eloquent\Builder|Issued newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Issued newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Issued query()
 * @method static \Illuminate\Database\Eloquent\Builder|Issued whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Issued whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Issued whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Issued whereShiftId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Issued whereUserId($value)
 * @mixin \Eloquent
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Shift $shift
 * @method static \Illuminate\Database\Eloquent\Builder|Issued whereUpdatedAt($value)
 */
class Issued extends Model
{
     use HasFactory;

	/**
     * The table associated with the model.
     *
     * @var string
     */
	protected $table = 'issueds';

	/**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

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
    public $incrementing = true;

	/**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
	protected $casts = [
		'user_id' 	=> 'int',
		'shift_id' 	=> 'int',
		'amount' 	=> 'float'
	];

	/**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
	protected $fillable = ['user_id', 'shift_id', 'amount', 'created_at', 'updated_at'];

	/**
     * One To Many (Inverse) / Belongs To
     */
	public function shift()
	{
		return $this->belongsTo(Shift::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
