<?php

// Remove SurveyInterior.php model completely

// Update SurveyIntCat.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveyIntCat extends Model
{
    protected $table = 'survey_int_cats';
    protected $fillable = ['survey_id', 'category_id'];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(InteriorCategory::class);
    }

    public function features(): HasMany
    {
        return $this->hasMany(SurveyIntCatFeature::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(SurveyIntCatImage::class);
    }
}