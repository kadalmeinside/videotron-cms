<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    protected $touches = ['schedule'];

    protected $casts = [
        'play_at' => 'datetime',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }


    /**
     * Setiap item jadwal dimiliki oleh satu Videotron.
     */
    public function videotron(): BelongsTo
    {
        return $this->belongsTo(Videotron::class);
    }

    /**
     * Mendefinisikan relasi "milik siapa".
     * Setiap item jadwal dimiliki oleh satu Media.
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }
}
