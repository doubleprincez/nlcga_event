<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('type')->nullable();
            $table->string('phone')->nullable();
            $table->string('from_company')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('governing__councils', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('position')->nullable();
            $table->text('bio')->nullable();
            $table->string('photo_url')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('depot_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $table->string('company_name')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->string('signal')->nullable();
            $table->string('color')->nullable();
            $table->string('address')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('email__templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('subject')->nullable();
            $table->longText('body')->nullable();
            $table->string('type')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('membership__payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('package_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('reference')->nullable()->unique();
            $table->string('status')->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('description')->nullable();
            $table->text('short_description')->nullable();
            $table->string('image_thumbnail')->nullable();
            $table->unsignedInteger('views_count')->default(0);
            $table->text('notes')->nullable();
            $table->string('display_status')->default('draft');
            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $table->string('slug')->nullable()->unique();
            $table->string('post_type')->default('news');
            $table->timestamps();
        });

        Schema::create('news_categories', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_url')->nullable();
            $table->string('thumbnail_image')->nullable();
            $table->string('package_desc')->nullable();
            $table->string('status')->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('post_type')->default('resource');
            $table->string('conference_year', 4)->nullable();
            $table->string('preview_link')->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('detail')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('resources');
        Schema::dropIfExists('news_categories');
        Schema::dropIfExists('news');
        Schema::dropIfExists('membership__payments');
        Schema::dropIfExists('email__templates');
        Schema::dropIfExists('depot_prices');
        Schema::dropIfExists('governing__councils');
        Schema::dropIfExists('contacts');
    }
};
