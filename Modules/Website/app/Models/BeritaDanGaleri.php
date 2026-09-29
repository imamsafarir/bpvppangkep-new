<?php

namespace Modules\Website\Models;

use Illuminate\Database\Eloquent\Model;

class BeritaDanGaleri extends Model
{
    protected $table = 'website_berita_dan_galeris';

    protected $fillable = [
        'jenis',
        'judul_berita',
        'tags',
        'konten_berita',
        'file_foto',
        'keterangan_galeri',
    ];

    protected $casts = [
        'tags' => 'array',
    ];
}
