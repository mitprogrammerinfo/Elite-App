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
        ['category_id' => null, 'name' => 'Carpet Flooring'],
        ['category_id' => null, 'name' => 'Vinyl Flooring'],
        ['category_id' => null, 'name' => 'Wood Baseboards'],
        ['category_id' => null, 'name' => 'Tile Baseboards'],
        ['category_id' => null, 'name' => 'Marble Baseboards'],
        ['category_id' => null, 'name' => 'Vinyl Baseboards'],
        ['category_id' => null, 'name' => 'Smooth Wall Finish'],
        ['category_id' => null, 'name' => 'Light Texture Wall Finish'],
        ['category_id' => null, 'name' => 'Knockdown Wall Finish'],
        ['category_id' => null, 'name' => 'Smooth Ceiling Finish'],
        ['category_id' => null, 'name' => 'Light Texture Ceiling Finish'],
        ['category_id' => null, 'name' => 'Knockdown Ceiling Finish'],
        ['category_id' => null, 'name' => 'Popcorn Ceiling Finish'],
    ];
}
}
