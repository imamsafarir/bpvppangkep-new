<?php

namespace Modules\Website\Models;

use Illuminate\Database\Eloquent\Model;

class Jdih extends Model
{
    protected $table = 'website_jdihs';

    protected $fillable = [
        'status_peraturan',
        'judul_peraturan',
        'nomor_peraturan',
        'file_path',
        'tentang',
        'jumlah_diunduh',
    ];
}
