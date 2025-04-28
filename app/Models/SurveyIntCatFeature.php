<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyIntCatFeature extends Model
{
    protected $table = 'survey_int_cat_features';
    protected $fillable = ['survey_int_cat_id', 'feature_id'];

    public function surveyCategory(): BelongsTo
    {
        return $this->belongsTo(SurveyIntCat::class);
    }

    public function feature(): BelongsTo
    {
        return $this->belongsTo(InteriorFeature::class);
    }
}
