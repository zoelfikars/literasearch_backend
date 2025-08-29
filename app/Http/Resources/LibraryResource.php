<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

class LibraryResource extends JsonResource
{
    public function toArray(Request $request)
    {
        $data = [
            "id" => $this->id,
            "name" => $this->name,
            "phone" => $this->phone_number,
            "description" => $this->description,
            "address" => $this->address,
            "latitude" => $this->latitude,
            "longitude" => $this->longitude,
            "is_recruiting" => $this->is_recruiting,
        ];
        if (!empty($this->latestApprovedByExpiration->expiration_date)) {
            $data['expiration_date'] = format_date($this->latestApprovedByExpiration->expiration_date);
        }
        if (!empty($this->image_path)) {
            $data['image'] = $this->cover_signed_url;
        }
        return $data;
    }
}
