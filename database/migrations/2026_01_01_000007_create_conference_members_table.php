<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conference_members', function (Blueprint $table) {
            $table->id();
            $table->string('conference_year', 4)->nullable();
            $table->string('email');
            $table->string('phoneNumber', 30)->nullable();
            $table->string('fullName');
            $table->string('passType')->nullable();
            $table->string('organisation')->nullable();
            $table->string('jobTitle')->nullable();
            $table->string('sector')->nullable();
            $table->json('interests')->nullable();
            $table->string('paymentMethod')->nullable();
            $table->decimal('amountPaid', 12, 2)->default(0);
            $table->string('nameOnPaymentAccount')->nullable();
            $table->string('exhibitorInterest')->nullable();
            $table->string('hearAboutUs')->nullable();
            $table->text('additionalInfo')->nullable();
            $table->string('unique_code', 20)->nullable()->unique();
            $table->string('qr_path')->nullable();
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
        Schema::dropIfExists('conference_members');
    }
};
