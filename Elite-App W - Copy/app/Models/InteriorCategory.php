<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InteriorCategory extends Model
{
    protected $fillable = ['name'];

    public function features(): HasMany
    {
        return $this->hasMany(InteriorFeature::class);
    }

    public static function getStaticCategories()
    {
        return [
            ['name' => 'Entry'],
            ['name' => 'Living Room'],
            ['name' => 'Office'],
            ['name' => 'Family Room'],
            ['name' => 'Dining Room'],
            ['name' => 'Kitchen'],
            ['name' => 'Primary Bedroom'],
            ['name' => 'Primary Kitchen'], 
            ['name' => 'Bedroom 1'],
            ['name' => 'Bedroom 2'],
            ['name' => 'Bedroom 3'],
            ['name' => 'Bedroom 4'],
            ['name' => 'Bedroom 5'],
            ['name' => 'Bedroom 6'],
            ['name' => 'Bathroom 1'],
            ['name' => 'Bathroom 2'],
            ['name' => 'Bathroom 3'],
            ['name' => 'Half bathroom'],
            ['name' => 'Stair well'],
            ['name' => 'Laundry'],
            ['name' => 'Garage'],
            ['name' => 'Patio'],
            ['name' => 'Other'],
        ];
    }
}
