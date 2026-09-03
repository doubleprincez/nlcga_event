<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $table = 'payments';

    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_UNDERPAID = 'underpaid';

    protected $fillable = [
        'conference_member_id', 'pass_type', 'gateway', 'reference', 'status',
        'amount_expected_kobo', 'amount_paid_kobo', 'currency', 'customer_email',
        'gateway_response', 'paid_at',
    ];

    protected $casts = [
        'gateway_response' => 'array',
        'paid_at' => 'datetime',
        'amount_expected_kobo' => 'integer',
        'amount_paid_kobo' => 'integer',
    ];

    public function conferenceMember(): BelongsTo
    {
        return $this->belongsTo(ConferenceMember::class);
    }

    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    public function markAsPaid(int $amountPaidKobo, array $gatewayResponse): void
    {
        $this->update([
            'status' => self::STATUS_SUCCESS,
            'amount_paid_kobo' => $amountPaidKobo,
            'gateway_response' => $gatewayResponse,
            'paid_at' => now(),
        ]);
    }
}

