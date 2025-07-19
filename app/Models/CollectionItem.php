<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollectionItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'type',
        'platform',
        'category',
        'condition_rating',
        'purchase_date',
        'purchase_price',
        'current_value',
        'location',
        'notes',
        'cover_image',
        'barcode',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'purchase_date' => 'date',
        'purchase_price' => 'decimal:2',
        'current_value' => 'decimal:2',
        'condition_rating' => 'integer',
    ];

    /**
     * Get the user that owns the collection item.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include items of a specific type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include items for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to search items by title or description.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('platform', 'like', "%{$search}%")
              ->orWhere('category', 'like', "%{$search}%");
        });
    }

    /**
     * Get the formatted purchase price.
     */
    public function getFormattedPurchasePriceAttribute()
    {
        return $this->purchase_price ? '€' . number_format($this->purchase_price, 2) : null;
    }

    /**
     * Get the formatted current value.
     */
    public function getFormattedCurrentValueAttribute()
    {
        return $this->current_value ? '€' . number_format($this->current_value, 2) : null;
    }

    /**
     * Get the condition rating as stars.
     */
    public function getConditionStarsAttribute()
    {
        return str_repeat('★', $this->condition_rating) . str_repeat('☆', 5 - $this->condition_rating);
    }

    /**
     * Check if item has a cover image.
     */
    public function hasCoverImage()
    {
        return !empty($this->cover_image);
    }

    /**
     * Get the cover image URL or placeholder.
     */
    public function getCoverImageUrlAttribute()
    {
        if ($this->hasCoverImage()) {
            return $this->cover_image;
        }
        
        // Return placeholder based on type
        return match($this->type) {
            'game' => '/images/placeholder-game.png',
            'film' => '/images/placeholder-film.png',
            'serie' => '/images/placeholder-serie.png',
            'book' => '/images/placeholder-book.png',
            'music' => '/images/placeholder-music.png',
            default => '/images/placeholder.png',
        };
    }
} 