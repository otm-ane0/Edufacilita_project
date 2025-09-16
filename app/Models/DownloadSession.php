<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DownloadSession extends Model
{
    protected $fillable = [
        'user_id',
        'selection_hash',
        'question_ids',
        'credit_amount',
        'download_type',
    ];

    protected $casts = [
        'question_ids' => 'array',
    ];

    /**
     * Get the user that owns the download session.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
