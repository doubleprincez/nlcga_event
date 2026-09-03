<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Community extends Model
{
    use HasFactory;

    protected $table = 'communities';

    protected $fillable = [
        'member_id', 'community__category_id', 'title', 'description',
        'restriction', 'status', 'slug', 'thumbnail_image_url',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CommunityCategory::class, 'community__category_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(CommunityComment::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(CommunityMember::class);
    }
}

