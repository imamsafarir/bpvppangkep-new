<?php

namespace Modules\Shortlink\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShortlinkLead extends Model
{
    protected $table = 'shortlink_leads';

    protected $fillable = [
        'shortlink_id',
        'nama',
        'whatsapp',
        'email',
        'ip_address',
        'user_agent',
    ];

    public function shortlink(): BelongsTo
    {
        return $this->belongsTo(Shortlink::class, 'shortlink_id');
    }
}
