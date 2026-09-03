<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ConferenceMember extends Model
{
    use HasFactory;

    protected $table = 'conference_members';

    protected $casts = ['interests' => 'array'];

    protected $fillable = [
        'conference_year', 'email', 'phoneNumber', 'fullName', 'passType',
        'organisation', 'jobTitle', 'sector', 'interests', 'paymentMethod',
        'amountPaid', 'nameOnPaymentAccount', 'exhibitorInterest', 'hearAboutUs',
        'additionalInfo', 'unique_code', 'qr_path', 'is_checked_in',
        'checked_in_at', 'checked_in_by', 'whatsapp_sent', 'email_sent',
    ];

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function aiChats(): HasMany
    {
        return $this->hasMany(BotCommunication::class, 'phone', 'phoneNumber');
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->conference_year)) {
                $model->conference_year = date('Y');
            }
        });
    }

    public function latestSuccessfulPayment(): ?Payment
    {
        return $this->payments()
            ->where('status', Payment::STATUS_SUCCESS)
            ->latest('paid_at')
            ->first();
    }

    public function generateUniqueCode(): void
    {
        do {
            $code = 'EVT-' . strtoupper(Str::random(6));
        } while (static::where('unique_code', $code)->exists());

        $this->unique_code = $code;
        $this->save();
    }
}

