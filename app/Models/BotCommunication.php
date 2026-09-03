<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotCommunication extends Model
{
    protected $table = 'bot_communications';
    protected $fillable = ['phone', 'message', 'direction'];
}

