<?php

namespace Modules\Sosmedhub\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Platform extends Model
{
    use HasFactory;

    protected $table = 'sosmedhub_platforms';

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function contents(): BelongsToMany
    {
        return $this->belongsToMany(Content::class, 'sosmedhub_content_platform', 'platform_id', 'content_id');
    }
}
