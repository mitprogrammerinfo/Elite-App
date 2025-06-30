<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Survey extends Model
{
    // Remove SoftDeletes trait if you want only permanent deletion
    // use SoftDeletes;
    
    protected $fillable = ['user_id', 'type', 'status'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function exteriorPhotos()
    {
        return $this->hasMany(ExteriorPhoto::class);
    }

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
    
     public function damageInspectionPhotos()
    {
        return $this->hasMany(DamageInspectionPhoto::class);
    }
    
   protected static function booted()
{
    static::deleting(function ($survey) {
        $survey->exteriorPhotos()->forceDelete();
        
        $survey->interiorCategories()->each(function ($category) {
            $category->images()->forceDelete();
            $category->features()->forceDelete();
            $category->forceDelete();
        });

        $survey->exteriorImages()->forceDelete();
        $survey->exteriorFeatures()->detach();
    });
}

}