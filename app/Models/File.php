<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    protected $fillable = [
        'file',
        'original_file',
        'description',
        'novedad_id',
    ];

    //Get novedad for the file
    public function novedad()
    {
        return $this->belongsTo('App\Models\Novedad');
    }
}
