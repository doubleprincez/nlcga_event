<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembershipPayment extends Model
{
    use HasFactory;

    protected $table = 'membership__payments';

    protected $fillable = [
        'member_id', 'package_id', 'amount', 'reference', 'status', 'paid_at',
    ];

    protected $casts = ['paid_at' => 'datetime'];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }
}

