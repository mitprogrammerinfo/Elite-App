<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExteriorSurveyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'survey_id' => $this->id,
            'status' => $this->status,
            'features' => $this->exteriorFeatures->map(function ($feature) {
                return [
                    'id' => $feature->id,
                    'name' => $feature->name,
                ];
            }),
            'images' => $this->exteriorImages->map(function ($image) {
                return [
                    'id' => $image->id,
                    'url' => asset('storage/' . $image->image_path),
                ];
            }),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
