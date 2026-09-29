<?php

namespace Modules\Website\Models;

use Illuminate\Database\Eloquent\Model;

class InformasiPublik extends Model
{
    protected $table = 'website_informasi_publiks';

    protected $fillable = [
        'kategori',
        'nama_dokumen',
        'file_path',
        'deskripsi',
        'jumlah_diunduh',
    ];
}
