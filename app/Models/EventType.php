<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventType extends Model
{
    use HasFactory;

    protected $table = 'eventtypes';

    protected $fillable = ['title', 'description'];

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'eventtype_id');
    }
}

