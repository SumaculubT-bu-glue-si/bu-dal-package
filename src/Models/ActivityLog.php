<?php

namespace Bu\Server\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'description',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get formatted metadata as JSON string.
     */
    public function getFormattedMetadataAttribute(): string
    {
        return $this->metadata ? json_encode($this->metadata, JSON_PRETTY_PRINT) : '';
    }

    /**
     * Get the action with proper formatting.
     */
    public function getFormattedActionAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->action));
    }

    /**
     * Get metadata field for GraphQL (safely serialize JSON).
     */
    public function getMetadataField(): ?string
    {
        if (!$this->metadata) {
            return null;
        }

        try {
            // If metadata is already a string, return it
            if (is_string($this->metadata)) {
                return $this->metadata;
            }

            // If metadata is an array, encode it as JSON
            if (is_array($this->metadata)) {
                return json_encode($this->metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }

            // For any other type, convert to string
            return (string) $this->metadata;
        } catch (\Exception $e) {
            // If serialization fails, return a safe string
            return '{"error": "Failed to serialize metadata"}';
        }
    }
}