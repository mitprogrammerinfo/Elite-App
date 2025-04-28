<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class SurveyExtImage extends Model
{
    protected $fillable = ['survey_id', 'image_path'];

    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }
}
