<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Playlist extends Model
{
    use HasFactory;
    
    protected $guarded = [];

    public function media()
    {
        return $this->belongsToMany(Media::class, 'media_playlist')->withPivot('play_order');
    }
    
    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function musics()
    {
        return $this->belongsToMany(Music::class, 'music_playlist')
                    ->withPivot('play_order')
                    ->withTimestamps();
    }

    public function videotrons()
    {
        return $this->hasMany(Videotron::class, 'playlist_id');
    }
}
