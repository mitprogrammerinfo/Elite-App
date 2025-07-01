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
            ['name' => 'Wood Frame'],
            ['name' => 'Unsure'],
            ['name' => 'Stucco'],
            ['name' => 'Vinyl Siding'],
            ['name' => 'Wood Siding'],
            ['name' => 'Hardy Board Siding'],
            ['name' => 'Tile'],
            ['name' => 'Shingle'],
            ['name' => 'Metal'],
            ['name' => 'Wood Shaker'],
            ['name' => 'Flat (Rolled On)'],
            ['name' => 'Flat (TPO)'],
            ['name' => 'Slate'],
            ['name' => 'Other'],
            ['name' => 'Fencing'],
            ['name' => 'Pool'],
            ['name' => 'Screened'],
            ['name' => 'Enclosure'],
            ['name' => 'Shed'],
            ['name' => 'Gazebo'],
            ['name' => 'Dock'],
            ['name' => 'Deck'],
            ['name' => 'Patio'],
        ];
    }
    
}
