<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('role_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('package_id')->nullable()->constrained()->nullOnDelete();
            $table->string('company_name')->nullable();
            $table->text('about_company')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->string('dpr_licence_number')->nullable();
            $table->string('rc_number')->nullable();
            $table->date('date_of_incorporation')->nullable();
            $table->text('activities')->nullable();
            $table->string('website')->nullable();
            $table->string('country')->nullable();
            $table->string('country_other')->nullable();
            $table->text('office_address')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('cover_photo_url')->nullable();
            $table->string('facebook_link')->nullable();
            $table->string('linkedin_link')->nullable();
            $table->string('twitter_link')->nullable();
            $table->boolean('is_active')->default(true);
            // Representative 1
            $table->string('representative1_fullname')->nullable();
            $table->string('representative1_surname')->nullable();
            $table->string('representative1_othername')->nullable();
            $table->string('representative1_email')->nullable();
            $table->string('representative1_phone')->nullable();
            $table->date('representative1_dob')->nullable();
            $table->string('representative1_facebook')->nullable();
            $table->string('representative1_linkedin')->nullable();
            $table->string('representative1_twitter')->nullable();
            $table->string('representative1_address')->nullable();
            $table->string('representative1_city')->nullable();
            $table->string('representative1_state')->nullable();
            $table->string('representative1_country')->nullable();
            $table->string('representative1_country_other')->nullable();
            $table->string('representative1_gender')->nullable();
            $table->string('representative1_designation')->nullable();
            // Representative 2
            $table->string('representative2_surname')->nullable();
            $table->string('representative2_othername')->nullable();
            $table->string('representative2_email')->nullable();
            $table->string('representative2_phone')->nullable();
            $table->date('representative2_dob')->nullable();
            $table->string('representative2_address')->nullable();
            $table->string('representative2_city')->nullable();
            $table->string('representative2_state')->nullable();
            $table->string('representative2_country')->nullable();
            $table->string('representative2_country_other')->nullable();
            $table->string('representative2_gender')->nullable();
            $table->string('representative2_designation')->nullable();
            $table->string('representative2_facebook')->nullable();
            $table->string('representative2_linkedin')->nullable();
            $table->string('representative2_twitter')->nullable();
            // Representative 3
            $table->string('representative3_surname')->nullable();
            $table->string('representative3_othername')->nullable();
            $table->string('representative3_email')->nullable();
            $table->string('representative3_phone')->nullable();
            $table->date('representative3_dob')->nullable();
            $table->string('representative3_facebook')->nullable();
            $table->string('representative3_linkedin')->nullable();
            $table->string('representative3_twitter')->nullable();
            // Documents
            $table->string('document1_url')->nullable();
            $table->string('document2_url')->nullable();
            $table->string('document3_url')->nullable();
            $table->string('promotion_photo1')->nullable();
            $table->string('promotion_photo2')->nullable();
            $table->string('promotion_photo3')->nullable();
            $table->boolean('agree_to_terms')->default(false);
            $table->string('isApproved', 5)->default('NO');
            $table->string('howDidYouFindOut')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
