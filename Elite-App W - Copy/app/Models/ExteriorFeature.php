<?php

// app/Models/ExteriorFeature.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExteriorFeature extends Model
{
    protected $fillable = ['survey_id','name'];

    public function surveys()
    {
        return $this->belongsToMany(Survey::class );
    }

    public static function getStaticExtFeatures()
    {
        return [
            ['name' => 'CBS Construction'],
            ['name' => 'Wood Frame Construction'],
            ['name' => 'Stucco'],
            ['name' => 'Vinyl Siding'],
            ['name' => 'Brick Siding'],
            ['name' => 'Gutters'],
            ['name' => 'Wood Fencing'],
            ['name' => 'Aluminum Fencing'],
            ['name' => 'Screen Enclosure'],
            ['name' => 'Tile Roof'],
            ['name' => 'Metal Roof'],
            ['name' => 'Shingle Roof'],
            ['name' => 'Flat Roof'],
        ];
    }
    
}
