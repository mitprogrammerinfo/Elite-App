<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/*class InteriorSurveyCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'category_name' => optional($this->category)->name,
            'images' => $this->images->map(function ($img) {
                return [
                    'id' => $img->id,  
                    'url' => asset('storage/' . $img->image_path)
                ];
            }),
            'features' => $this->features->map(function ($feature) {
                return [
                    'id' => $feature->feature_id, 
                    'name' => optional($feature->feature)->name,
                ];
            }),
        ];
    }
}
*/
class InteriorSurveyCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'survey_type' => optional($this->survey)->type,
            'category_id' => $this->category_id,
            'category_name' => optional($this->category)->name,
            'images' => $this->images->map(function ($img) {
                return [
                    'id' => $img->id,
                    'url' => asset('storage/' . $img->image_path)
                ];
            }),
            'features' => $this->features->map(function ($feature) {
                return [
                    'id' => $feature->feature_id,
                    'name' => optional($feature->feature)->name,
                ];
            }),
        ];
    }
}

