<?php

namespace Modules\Shortlink\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shortlink extends Model
{
    protected $table = 'shortlink_links';

    protected $fillable = [
        'pegawai_name',
        'code',
        'destination_url',
        'clicks_count',
        'is_capture_active',
        'capture_fields',
        'custom_title',
        'custom_description',
        'custom_button_text',
        'spreadsheet_webhook_url',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'capture_fields'    => 'array',
        'is_capture_active' => 'boolean',
        'is_active'         => 'boolean',
        'clicks_count'      => 'integer',
    ];

    protected $appends = ['short_url'];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(ShortlinkLead::class, 'shortlink_id');
    }

    public function getShortUrlAttribute(): string
    {
        return url('/s/' . $this->code);
    }
}
