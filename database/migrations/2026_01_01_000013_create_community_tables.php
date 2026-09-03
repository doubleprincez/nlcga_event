<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community__categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('communities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('community__category_id')->nullable();
            $table->foreign('community__category_id')->references('id')->on('community__categories')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('restriction')->default('public');
            $table->string('status')->default('active');
            $table->string('slug')->nullable()->unique();
            $table->string('thumbnail_image_url')->nullable();
            $table->timestamps();
        });

        Schema::create('community__comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained('communities')->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $table->text('content');
            $table->timestamps();
        });

        Schema::create('community__members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained('communities')->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $table->string('role')->default('member');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community__members');
        Schema::dropIfExists('community__comments');
        Schema::dropIfExists('communities');
        Schema::dropIfExists('community__categories');
    }
};
