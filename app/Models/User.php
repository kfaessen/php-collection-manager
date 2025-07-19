<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'email',
        'password_hash',
        'first_name',
        'last_name',
        'is_active',
        'last_login',
        'failed_login_attempts',
        'locked_until',
        'totp_secret',
        'totp_enabled',
        'totp_backup_codes',
        'email_verified',
        'email_verification_token',
        'email_verification_expires',
        'avatar_url',
        'registration_method',
        'preferred_language',
        'notifications_enabled',
        'email_notifications',
        'push_notifications',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password_hash',
        'totp_secret',
        'totp_backup_codes',
        'email_verification_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'last_login' => 'datetime',
        'locked_until' => 'datetime',
        'totp_enabled' => 'boolean',
        'totp_backup_codes' => 'array',
        'email_verified' => 'boolean',
        'email_verification_expires' => 'datetime',
        'notifications_enabled' => 'boolean',
        'email_notifications' => 'boolean',
        'push_notifications' => 'boolean',
    ];

    /**
     * Get the password for the user.
     */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Check if the user is locked.
     */
    public function isLocked()
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    /**
     * Check if the user has TOTP enabled.
     */
    public function hasTOTPEnabled()
    {
        return $this->totp_enabled && $this->totp_secret;
    }

    /**
     * Get the user's collection items.
     */
    public function collectionItems()
    {
        return $this->hasMany(CollectionItem::class);
    }

    /**
     * Get the user's groups.
     */
    public function groups()
    {
        return $this->belongsToMany(Group::class, 'user_groups');
    }

    /**
     * Get the user's permissions through groups.
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'group_permissions', 'group_id', 'permission_id')
            ->wherePivotIn('group_id', $this->groups->pluck('id'));
    }

    /**
     * Check if user has a specific permission.
     */
    public function hasPermission($permission)
    {
        return $this->permissions()->where('name', $permission)->exists();
    }

    /**
     * Check if user is in a specific group.
     */
    public function isInGroup($groupName)
    {
        return $this->groups()->where('name', $groupName)->exists();
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin()
    {
        return $this->isInGroup('admin');
    }
} 