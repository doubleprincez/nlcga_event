<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityMember extends Model
{
    protected $table = 'community__members';

    protected $fillable = ['community_id', 'member_id', 'role'];

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}

