<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Feedback extends Model
{
    use HasFactory;

    protected $table = 'feedbacks';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'category_id',
        'content',
        'ip_hash',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the category that this feedback belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the account that submitted this feedback.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the sentiment analysis result for this feedback.
     */
    public function sentimentResult(): HasOne
    {
        return $this->hasOne(SentimentResult::class);
    }

    /**
     * Scope to only pending (unanalyzed) feedback.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to only analyzed feedback.
     */
    public function scopeAnalyzed($query)
    {
        return $query->where('status', 'analyzed');
    }

    /**
     * Scope to feedback that may appear in faculty-facing reports and analytics.
     */
    public function scopeReportable($query)
    {
        return $query->where('status', '!=', 'rejected');
    }
}
