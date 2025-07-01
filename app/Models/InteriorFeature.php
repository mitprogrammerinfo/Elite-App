<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InteriorFeature extends Model
{
    protected $fillable = ['category_id', 'name'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(InteriorCategory::class);
    }

    public static function getStaticFeatures()
{
    return [
        ['category_id' => null, 'name' => 'Tile Flooring'],
        ['category_id' => null, 'name' => 'Wood Flooring'],
        ['category_id' => null, 'name' => 'Marble Flooring'],
        ['category_id' => null, 'name' => 'Laminate Flooring'],
        ['category_id' => null, 'name' => 'Vinyl Flooring'],
        ['category_id' => null, 'name' => 'Finished Concrete Flooring'],
        ['category_id' => null, 'name' => 'Carpet Flooring'],
        ['category_id' => null, 'name' => 'No Flooring'],
        ['category_id' => null, 'name' => 'Tile Baseboard'],
        ['category_id' => null, 'name' => 'Wood Baseboard'],
        ['category_id' => null, 'name' => 'Vinyl Baseboard'],
        ['category_id' => null, 'name' => 'Marble Baseboard'],
        ['category_id' => null, 'name' => 'No Baseboard'],
        ['category_id' => null, 'name' => 'Yes'],
        ['category_id' => null, 'name' => 'No'],
        ['category_id' => null, 'name' => 'Smooth Ceiling Finish'],
        ['category_id' => null, 'name' => 'Light Texture Ceiling Finish'],
        ['category_id' => null, 'name' => 'Knockdown Ceiling Finish'],
        ['category_id' => null, 'name' => 'Artistic Texture Finish'],
    ];
}
}
