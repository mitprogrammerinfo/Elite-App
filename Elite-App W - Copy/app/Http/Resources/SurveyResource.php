<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SurveyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'survey_id' => $this->id,
            'status' => $this->status,
            'type' => $this->type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'exterior_photos' => ExteriorPhotoResource::collection($this->exteriorPhotos),
            'exterior_features' => $this->exteriorFeatures->map(fn($f) => [
                'id' => $f->id,
                'name' => $f->name,
            ]),
            'exterior_images' => $this->exteriorImages->map(fn($img) => [
                'id' => $img->id,
                'image_path' => asset('storage/' . $img->image_path),
            ]),
            'interior_categories' => InteriorSurveyCategoryResource::collection($this->interiorCategories),
        ];
    }
}

