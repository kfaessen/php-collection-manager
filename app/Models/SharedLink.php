<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SharedLink extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'item_id',
        'token',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Get the user that owns the shared link.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the collection item that is being shared.
     */
    public function item()
    {
        return $this->belongsTo(CollectionItem::class);
    }

    /**
     * Scope a query to only include active (non-expired) shared links.
     */
    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Check if the shared link is still active.
     */
    public function isActive()
    {
        return $this->expires_at->isFuture();
    }

    /**
     * Check if the shared link has expired.
     */
    public function isExpired()
    {
        return $this->expires_at->isPast();
    }
}