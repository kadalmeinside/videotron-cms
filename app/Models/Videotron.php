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

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function scheduleItems(): HasMany
    {
        return $this->hasMany(ScheduleItem::class);
    }

    public function getRouteKeyName()
    {
        return 'id';
    }
}
