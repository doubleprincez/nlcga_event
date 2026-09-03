<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    use HasFactory;

    protected $table = 'resources';

    protected $fillable = [
        'title', 'description', 'file_url', 'thumbnail_image',
        'package_desc', 'status', 'created_by', 'post_type',
        'conference_year', 'preview_link',
    ];
}

