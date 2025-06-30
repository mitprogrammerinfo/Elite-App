<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExteriorPhoto extends Model
{
    protected $fillable = ['survey_id', 'image_path','label'];

    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }
}