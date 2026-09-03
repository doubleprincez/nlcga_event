<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends Model
{
    use HasFactory;

    protected $table = 'members';

    protected $fillable = [
        'user_id', 'role_id', 'package_id', 'company_name', 'about_company',
        'email', 'phone', 'mobile', 'dpr_licence_number', 'rc_number',
        'date_of_incorporation', 'activities', 'website', 'country',
        'country_other', 'office_address', 'logo_url', 'cover_photo_url',
        'facebook_link', 'linkedin_link', 'twitter_link', 'is_active',
        'representative1_fullname', 'representative1_surname', 'representative1_othername',
        'representative1_email', 'representative1_phone', 'representative1_dob',
        'representative1_facebook', 'representative1_linkedin', 'representative1_twitter',
        'representative1_address', 'representative1_city', 'representative1_state',
        'representative1_country', 'representative1_country_other', 'representative1_gender',
        'representative1_designation', 'representative2_surname', 'representative2_othername',
        'representative2_email', 'representative2_phone', 'representative2_dob',
        'representative2_address', 'representative2_city', 'representative2_state',
        'representative2_country', 'representative2_country_other', 'representative2_gender',
        'representative2_designation', 'representative2_facebook', 'representative2_linkedin',
        'representative2_twitter', 'representative3_surname', 'representative3_othername',
        'representative3_email', 'representative3_phone', 'representative3_dob',
        'representative3_facebook', 'representative3_linkedin', 'representative3_twitter',
        'document1_url', 'document2_url', 'document3_url',
        'promotion_photo1', 'promotion_photo2', 'promotion_photo3',
        'agree_to_terms', 'isApproved', 'howDidYouFindOut',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function depotPrices(): HasMany
    {
        return $this->hasMany(DepotPrice::class);
    }
}

