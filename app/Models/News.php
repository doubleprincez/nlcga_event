<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    use HasFactory;

    protected $table = 'news';

    protected $fillable = [
        'title', 'description', 'short_description', 'image_thumbnail',
        'views_count', 'notes', 'display_status', 'member_id', 'slug', 'post_type',
    ];
}

