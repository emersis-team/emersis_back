<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Novedad extends Model
{
    protected $table = 'news';

    protected $fillable = [
        'titulo','descripcion', 'activo'
    ];

}
