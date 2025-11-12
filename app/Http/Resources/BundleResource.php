<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BundleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'bundle_id' => $this->bundle_id,
            'org_id' => $this->org_id,
            'bundle_name' => $this->bundle_name,
            'description' => $this->description,
            'offerings' => $this->offerings,
            'price' => $this->price,
            'discount_percentage' => $this->discount_percentage,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
