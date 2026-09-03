<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('eventtype_id')->nullable()->constrained('event_types')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('event_date')->nullable();
            $table->time('event_time')->nullable();
            $table->string('tags')->nullable();
            $table->string('cover_image')->nullable();
            $table->boolean('charge')->default(false);
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('status')->default('draft');
            $table->string('event_catgory')->nullable();
            $table->string('currency')->default('NGN');
            $table->string('slug')->nullable()->unique();
            $table->string('meeting_link')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
