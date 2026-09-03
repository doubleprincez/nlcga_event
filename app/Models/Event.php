<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    protected $table = 'events';

    protected $fillable = [
        'member_id', 'eventtype_id', 'title', 'description', 'event_date',
        'event_time', 'tags', 'cover_image', 'charge', 'amount', 'status',
        'event_catgory', 'currency', 'slug', 'meeting_link',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function eventType(): BelongsTo
    {
        return $this->belongsTo(EventType::class, 'eventtype_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(EventParticipant::class);
    }
}

