<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Registration extends Model
{
    protected $table = 'registrations';
    protected $fillable = [
        'name', 'email', 'phone', 'unique_code', 'is_checked_in',
        'checked_in_at', 'checked_in_by', 'email_sent', 'whatsapp_sent',
    ];

    protected $casts = [
        'is_checked_in' => 'boolean',
        'email_sent' => 'boolean',
        'whatsapp_sent' => 'boolean',
        'checked_in_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($registration) {
            if (empty($registration->unique_code)) {
                $registration->unique_code = self::generateUniqueCode();
            }
            if ($registration->phone) {
                $registration->phone = preg_replace('/[^0-9]/', '', $registration->phone);
            }
        });
    }

    protected static function generateUniqueCode(): string
    {
        $chars = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';
        $maxAttempts = 10;
        $attempt = 0;

        do {
            $attempt++;
            $code = '';
            for ($i = 0; $i < 5; $i++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $fullCode = 'EVT-' . $code;
            if (!self::where('unique_code', $fullCode)->exists()) {
                return $fullCode;
            }
        } while ($attempt < $maxAttempts);

        return 'EVT-' . strtoupper(substr(md5(microtime(true) . random_int(1000, 9999)), 0, 5));
    }

    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    public function checkInLogs(): HasMany
    {
        return $this->hasMany(CheckInLog::class);
    }
}

