<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunityCategory extends Model
{
    protected $table = 'community__categories';

    protected $fillable = ['name', 'description'];

    public function communities(): HasMany
    {
        return $this->hasMany(Community::class, 'community__category_id');
    }
}

