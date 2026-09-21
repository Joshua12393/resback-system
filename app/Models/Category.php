<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    public const FEEDBACK_CATEGORIES = [
        'CCIS',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'icon',
        'description',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static function ccis(): self
    {
        $category = self::query()->firstOrCreate(
            ['slug' => 'ccis'],
            [
                'name' => 'CCIS',
                'icon' => '🏫',
                'description' => 'Feedback intended for CCIS',
                'is_active' => true,
            ]
        );

        if (! $category->is_active) {
            $category->update(['is_active' => true]);
        }

        return $category;
    }

    /**
     * Get all feedbacks under this category.
     */
    public function feedbacks(): HasMany
    {
        return $this->hasMany(Feedback::class);
    }

    /**
     * Get concern rankings for this category.
     */
    public function concernRankings(): HasMany
    {
        return $this->hasMany(ConcernRanking::class);
    }

    /**
     * Scope to active categories only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
