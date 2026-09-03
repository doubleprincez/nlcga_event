<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckInLog extends Model
{
    protected $table = 'check_in_logs';
    protected $fillable = ['registration_id', 'checked_in_by', 'checked_in_at'];

    protected function casts(): array
    {
        return ['checked_in_at' => 'datetime'];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }
}

