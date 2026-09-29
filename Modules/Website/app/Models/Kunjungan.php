<?php

namespace Modules\Website\Models;

use Illuminate\Database\Eloquent\Model;

class Kunjungan extends Model
{
    protected $table = 'website_kunjungans';

    protected $fillable = [
        'ip_address',
        'tanggal',
    ];
}
