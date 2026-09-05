<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppTemplate extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_templates';

    protected $fillable = [
        'name', 'display_name', 'content_sid', 'description',
        'category', 'status', 'variables', 'is_default',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_default' => 'boolean',
    ];

    public static function getByName(string $name): ?self
    {
        return self::where('name', $name)->where('status', 'active')->first();
    }

    public static function getContentSid(string $name): ?string
    {
        return self::where('name', $name)->where('status', 'active')->value('content_sid');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}

