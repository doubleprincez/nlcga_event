<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_communications', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 30);
            $table->text('message');
            $table->enum('direction', ['inbound', 'outbound']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_communications');
    }
};
