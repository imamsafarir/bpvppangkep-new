<?php

namespace Modules\Sosmedhub\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Content extends Model
{
    use HasFactory;

    protected $table = 'sosmedhub_contents';

    protected $fillable = [
        'planner_id',
        'editor_id',
        'admin_id',
        'instruktur_id',
        'pegawai_id',
        'jenis_konten',
        'nama_kegiatan',
        'tanggal_kegiatan',
        'rencana_tayang',
        'brief',
        'caption',
        'link_referensi',
        'link_media_mentah',
        'link_hasil_edit',
        'link_postingan',
        'status',
        'tanggal_posting',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_kegiatan' => 'date:Y-m-d',
            'rencana_tayang'   => 'date:Y-m-d',
            'tanggal_posting'  => 'date:Y-m-d',
        ];
    }

    public function platforms(): BelongsToMany
    {
        return $this->belongsToMany(Platform::class, 'sosmedhub_content_platform', 'content_id', 'platform_id');
    }

    public function planner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'planner_id');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editor_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function instruktur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instruktur_id');
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pegawai_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(Revision::class, 'content_id')->latest();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'content_id')->latest();
    }

    public function reads(): HasMany
    {
        return $this->hasMany(ContentRead::class, 'content_id');
    }
}
