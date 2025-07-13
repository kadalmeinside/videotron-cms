<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Music extends Model
{
    use HasFactory;
    protected $table = 'musics';
    protected $guarded = [];

    public function playlists(): BelongsToMany
    {
        return $this->belongsToMany(Playlist::class, 'music_playlist')
                    ->withPivot('play_order')
                    ->withTimestamps();
    }
}
