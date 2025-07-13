<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
// --- PERBAIKAN: Import HasMany dari namespace yang benar ---
use Illuminate\Database\Eloquent\Relations\HasMany; 


class Schedule extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function videotron()
    {
        return $this->hasMany(Videotron::class);
    }
    
    public function playlist()
    {
        return $this->belongsTo(Playlist::class);
    }

    public function scheduleItems(): HasMany
    {
        return $this->hasMany(ScheduleItem::class)->orderBy('play_at');
    }
}
