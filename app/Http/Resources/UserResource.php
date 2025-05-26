<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;


class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'email_verified_at'=>$this->email_verified_at,
            'avatar' => $this->formatAvatar($this->avatar),    
            'address_line_1'=>$this->address_line_1,
            'address_line_2'=>$this->address_line_2,
            'city'=>$this->city,
            'zip'=>$this->zip,
            'state'=>$this->state,
            'verification_code'=>$this->verification_code,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function formatAvatar(?string $avatar): ?string
    {
        if (!$avatar) {
            return null;
        }

        // If it's a Google avatar URL or any external URL, return as-is
        if (Str::startsWith($avatar, ['http://', 'https://'])) {
            return $avatar;
        }

        // Otherwise, treat it as a local storage path
        return asset('storage/' . $avatar);
    }


}

