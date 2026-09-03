<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 20);
            $table->string('unique_code', 20)->unique();
            $table->boolean('is_checked_in')->default(false);
            $table->timestamp('checked_in_at')->nullable();
            $table->unsignedBigInteger('checked_in_by')->nullable();
            $table->boolean('whatsapp_sent')->default(false);
            $table->boolean('email_sent')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
