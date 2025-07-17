<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
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
        'module',
    ];

    /**
     * Get the roles that have this permission.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Get the users that have this permission through their roles.
     */
    public function users()
    {
        return $this->roles()->with('users')->get()->flatMap(function ($role) {
            return $role->users;
        });
    }

    /**
     * Scope to filter by module.
     */
    public function scopeByModule($query, $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Get all available modules.
     */
    public static function getModules()
    {
        return static::distinct()->pluck('module')->sort()->values();
    }
}
