<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function videotron()
    {
        return $this->belongsTo(Videotron::class);
    }
    
    public function playlist()
    {
        return $this->belongsTo(Playlist::class);
    }
}
