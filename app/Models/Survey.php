<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Survey extends Model
{
    protected $fillable = ['user_id', 'status'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function exteriorPhotos()
    {
        return $this->hasMany(ExteriorPhoto::class);
    }

    // updating
    public function interiorCategories()
{
    return $this->hasMany(SurveyIntCat::class);
}

public function exteriorImages()
{
    return $this->hasMany(SurveyExtImage::class);
}

public function exteriorFeatures()
{
    return $this->belongsToMany(ExteriorFeature::class, 'survey_ext_features', 'survey_id', 'feature_id')
                ->using(SurveyExtFeature::class)
                ->withTimestamps();
}
}