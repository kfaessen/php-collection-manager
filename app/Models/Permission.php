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
        'description',
    ];

    /**
     * Get the groups that have this permission.
     */
    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_permissions');
    }

    /**
     * Get the users that have this permission through groups.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'group_permissions', 'permission_id', 'group_id')
            ->wherePivotIn('group_id', function($query) {
                $query->select('group_id')->from('user_groups');
            });
    }
} 