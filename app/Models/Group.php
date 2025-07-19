<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Get the users in this group.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_groups');
    }

    /**
     * Get the permissions for this group.
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'group_permissions');
    }

    /**
     * Check if group has a specific permission.
     */
    public function hasPermission($permissionName)
    {
        return $this->permissions()->where('name', $permissionName)->exists();
    }
} 