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
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'username' => $this->username,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'is_active' => $this->is_active,
            'email_verified_at' => $this->email_verified_at,
            'last_login' => $this->last_login,
            'avatar_url' => $this->avatar_url,
            'registration_method' => $this->registration_method,
            'preferred_language' => $this->preferred_language,
            'notifications_enabled' => $this->notifications_enabled,
            'email_notifications' => $this->email_notifications,
            'push_notifications' => $this->push_notifications,
            'totp_enabled' => $this->totp_enabled,
            'roles' => RoleResource::collection($this->whenLoaded('roles')),
            'permissions' => $this->when($request->user()?->hasPermissionTo('users.view_permissions'), 
                $this->permissions->pluck('name')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
