<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncedPlayLog extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'logged_at' => 'datetime',
    ];

    /**
     * Setiap log dimiliki oleh satu Videotron.
     */
    public function videotron(): BelongsTo
    {
        return $this->belongsTo(Videotron::class);
    }
}
