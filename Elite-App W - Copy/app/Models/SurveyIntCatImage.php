<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyIntCatImage extends Model
{
    protected $table = 'survey_int_cat_images';
    protected $fillable = ['survey_int_cat_id', 'image_path'];

    public function surveyCategory(): BelongsTo
    {
        return $this->belongsTo(SurveyIntCat::class);
    }
}
