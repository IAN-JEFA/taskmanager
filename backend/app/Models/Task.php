<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'due_date',
        'priority',
        'status',
    ];

    protected $casts = [
        'due_date' => 'date:Y-m-d',
    ];

    /**
     * Allowed forward-only status progression.
     * pending -> in_progress -> done. No skipping, no reverting.
     */
    public const STATUS_FLOW = [
        'pending' => 'in_progress',
        'in_progress' => 'done',
        'done' => null,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function canTransitionTo(string $newStatus): bool
    {
        return self::STATUS_FLOW[$this->status] === $newStatus;
    }
}
