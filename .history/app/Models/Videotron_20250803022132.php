<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids; 
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Videotron extends Model
{
    use HasFactory, SoftDeletes, HasUuids, HasApiTokens, Authenticatable;
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $guarded = [];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function getIsOnlineAttribute(): bool
    {
        return $this->last_seen_at && $this->last_seen_at->gt(now()->subMinutes(16));
    }

    /**
     * Accessor untuk memformat waktu terakhir terlihat agar mudah dibaca.
     */
    public function getLastSeenAtForHumansAttribute(): string
    {
        return $this->last_seen_at ? $this->last_seen_at->diffForHumans() : 'Belum pernah';
    }
    
    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }
    
    public function scheduleItems(): HasMany
    {
        return $this->hasMany(ScheduleItem::class);
    }

    public function backgroundPlaylist()
    {
        return $this->belongsTo(Playlist::class, 'playlist_id');
    }


    public function getRouteKeyName()
    {
        return 'id';
    }
}
