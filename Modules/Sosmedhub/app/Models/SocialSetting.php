<?php

namespace Modules\Sosmedhub\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialSetting extends Model
{
    use HasFactory;

    protected $table = 'sosmedhub_social_settings';

    protected $fillable = [
        'provider_name',
        'app_id',
        'app_secret',
        'page_id',
        'access_token',
        'ig_user_id',
    ];
}
