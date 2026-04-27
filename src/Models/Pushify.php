<?php

namespace Badawy\Pushify\Models;

use Illuminate\Database\Eloquent\Model;

class Pushify extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';

    protected $table = 'pushify_notifications';

    protected $guarded = [];

    protected $casts = [
        'data' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function isScheduledForFuture(): bool
    {
        return $this->scheduled_at !== null && $this->scheduled_at->isFuture();
    }
}
