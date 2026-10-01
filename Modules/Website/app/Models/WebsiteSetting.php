<?php

namespace Modules\Website\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteSetting extends Model
{
    protected $table = 'website_settings';

    protected $fillable = [
        'website_name',
        'logo_path',
        'favicon_path',
        'sliders',
        'email',
        'whatsapp_number',
        'phone_number',
        'address',
        'google_maps_embed',
        'facebook_url',
        'instagram_url',
        'youtube_url',
        'tiktok_url',
        'is_popup_active',
        'popup_image_path',
        'popup_redirect_url',
        'is_running_text_active',
        'running_text_content',
    ];

    protected $casts = [
        'sliders'                => 'array',
        'is_popup_active'        => 'boolean',
        'is_running_text_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(function () {
            \Illuminate\Support\Facades\Cache::forget('shared_website_settings');
            \Illuminate\Support\Facades\Cache::forget('website_home_stats');
        });

        static::deleted(function () {
            \Illuminate\Support\Facades\Cache::forget('shared_website_settings');
            \Illuminate\Support\Facades\Cache::forget('website_home_stats');
        });
    }
}
