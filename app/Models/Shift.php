<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * App\Models\Shift
 *
 * @property int $id
 * @property int $chat_id
 * @property float $rate
 * @property bool $is_start
 * @property bool $is_end
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property-read \App\Models\Chat $chat
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Deposit[] $deposits
 * @property-read int|null $deposits_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Issued[] $issueds
 * @property-read int|null $issueds_count
 * @method static \Illuminate\Database\Eloquent\Builder|Shift newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Shift newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Shift query()
 * @method static \Illuminate\Database\Eloquent\Builder|Shift whereChatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shift whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shift whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shift whereIsEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shift whereIsStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shift whereRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shift whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Shift extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'shifts';

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
        'chat_id'   => 'int',
		'rate'      => 'float',
		'is_start'  => 'bool',
		'is_end'    => 'bool'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
	protected $fillable = [
		'chat_id',
		'rate',
		'is_start',
		'is_end',
        'start_date',
        'end_date',
	];

    /**
     * One To Many (Inverse) / Belongs To
     */
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
