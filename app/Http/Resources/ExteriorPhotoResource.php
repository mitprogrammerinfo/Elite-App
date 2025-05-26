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
            'label' => $this->label,
            'image_path' =>  asset('storage/' . $this->image_path), 
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
