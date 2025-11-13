<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->user_id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'profile_picture' => $this->profile_picture,
            'timezone' => $this->timezone,
            'language' => $this->language,
            'preferences' => $this->preferences,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships
            'roles' => $this->whenLoaded('roles', function() {
                return $this->roles->map(function($role) {
                    return [
                        'id' => $role->role_id,
                        'name' => $role->role_name,
                        'org_id' => $role->pivot->org_id ?? null,
                    ];
                });
            }),

            'organizations' => OrgResource::collection($this->whenLoaded('orgs')),

            // Computed fields
            'initials' => $this->getInitials(),
            'is_verified' => !is_null($this->email_verified_at),
        ];
    }

    protected function getInitials(): string
    {
        $names = explode(' ', $this->name);
        $initials = '';

        foreach ($names as $name) {
            $initials .= strtoupper(substr($name, 0, 1));
        }

        return substr($initials, 0, 2);
    }
}
