<?php

// app/Models/SurveyExtFeature.php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class SurveyExtFeature extends Pivot
{
    protected $table = 'survey_ext_features';
    
    protected $fillable = [
        'survey_id',
        'feature_id'
    ];
    
    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }
}
