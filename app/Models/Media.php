<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasFactory;
    
    protected $guarded = [];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function playlists()
    {
        return $this->belongsToMany(Playlist::class, 'media_playlist')->withPivot('play_order');
    }

    public function getPreviewUrlAttribute(): ?string
    {
        // Hanya generate URL jika tipenya 'local' dan ada path file
        if ($this->source_type === 'local' && $this->source_value) {
            return Storage::url($this->source_value);
        }

        // Jika tidak, kembalikan null
        return null;
    }
}
