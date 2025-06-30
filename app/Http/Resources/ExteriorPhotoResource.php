<?php

// app/Http/Resources/ExteriorPhotoResource.php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExteriorPhotoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'survey_id' => $this->survey_id,
            'image_path' => asset('storage/' . $this->image_path),
            'room_index' => $this->room_index,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

