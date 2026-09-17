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
        $sid = self::where('name', $name)->where('status', 'active')->value('content_sid');
        if ($sid) {
            return trim($sid);
        }

        // Fallback to alias if applicable
        if ($name === 'payment_completed' || $name === 'payment_confirmed') {
            $aliasSid = self::whereIn('name', ['payment_completed', 'payment_confirmed'])
                ->where('status', 'active')
                ->whereNotNull('content_sid')
                ->value('content_sid');
            if ($aliasSid) {
                return trim($aliasSid);
            }
        }

        // Fallback to config / env
        return config("services.twilio.templates.{$name}")
            ?: config('services.twilio.templates.default')
            ?: env('TWILIO_DEFAULT_TEMPLATE_SID');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}

