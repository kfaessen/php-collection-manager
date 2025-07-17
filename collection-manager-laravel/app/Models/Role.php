<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'is_system',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_system' => 'boolean',
    ];

    /**
     * Get the users that belong to this role.
     */
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * Get the permissions that belong to this role.
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }

    /**
     * Check if role has a specific permission.
     */
    public function hasPermission($permission)
    {
        if (is_string($permission)) {
            return $this->permissions()->where('name', $permission)->exists();
        }
        return $this->permissions()->contains($permission);
    }

    /**
     * Check if role has any of the given permissions.
     */
    public function hasAnyPermission($permissions)
    {
        if (is_string($permissions)) {
            return $this->hasPermission($permissions);
        }
        
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if role has all of the given permissions.
     */
    public function hasAllPermissions($permissions)
    {
        if (is_string($permissions)) {
            return $this->hasPermission($permissions);
        }
        
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Assign permissions to this role.
     */
    public function assignPermissions($permissions)
    {
        if (is_string($permissions)) {
            $permissions = [$permissions];
        }
        
        $permissionIds = Permission::whereIn('name', $permissions)->pluck('id');
        $this->permissions()->syncWithoutDetaching($permissionIds);
    }

    /**
     * Remove permissions from this role.
     */
    public function removePermissions($permissions)
    {
        if (is_string($permissions)) {
            $permissions = [$permissions];
        }
        
        $permissionIds = Permission::whereIn('name', $permissions)->pluck('id');
        $this->permissions()->detach($permissionIds);
    }

    /**
     * Sync permissions for this role.
     */
    public function syncPermissions($permissions)
    {
        if (is_string($permissions)) {
            $permissions = [$permissions];
        }
        
        $permissionIds = Permission::whereIn('name', $permissions)->pluck('id');
        $this->permissions()->sync($permissionIds);
    }

    /**
     * Check if this is a system role.
     */
    public function isSystem()
    {
        return $this->is_system;
    }
}
