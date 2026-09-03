<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conference_member_id')->constrained('conference_members')->cascadeOnDelete();
            $table->string('pass_type')->nullable();
            $table->string('gateway')->default('paystack');
            $table->string('reference')->unique();
            $table->string('status')->default('pending');
            $table->integer('amount_expected_kobo')->nullable();
            $table->integer('amount_paid_kobo')->nullable();
            $table->string('currency')->default('NGN');
            $table->string('customer_email')->nullable();
            $table->json('gateway_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
