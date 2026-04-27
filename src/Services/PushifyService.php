<?php

namespace Badawy\Pushify\Services;

use Badawy\Pushify\Contracts\PushifyServiceInterface;
use Badawy\Pushify\Factories\PushifyProviderFactory;
use Badawy\Pushify\Models\Pushify;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class PushifyService implements PushifyServiceInterface
{
    public function __construct(
        private readonly PushifyProviderFactory $factory
    ) {}

    public function create(array $payload): Pushify
    {
        return Pushify::query()->create([
            'title' => $payload['title'],
            'body' => $payload['body'],
            'image' => $payload['image'] ?? null,
            'data' => $payload['data'] ?? [],
            'scheduled_at' => $payload['scheduled_at'] ?? null,
            'status' => Pushify::STATUS_PENDING,
        ]);
    }

    public function sendToAll(
        string $title,
        string $body,
        array $data = [],
        ?string $image = null,
        ?string $scheduledAt = null
    ): Pushify {
        $notification = $this->create([
            'title' => $title,
            'body' => $body,
            'image' => $image,
            'data' => $data,
            'scheduled_at' => $scheduledAt,
        ]);

        $provider = config('pushify.provider', 'firebase');
        $scheduledDate = $scheduledAt ? Carbon::parse($scheduledAt) : null;

        if ($provider === 'firebase' && $scheduledDate?->isFuture()) {
            return $notification->refresh();
        }

        return $this->send($notification);
    }

    public function send(Pushify $notification): Pushify
    {
        $providerName = config('pushify.provider', 'firebase');
        $scheduledAt = $notification->scheduled_at?->toIso8601String();

        try {
            $notification->forceFill([
                'status' => Pushify::STATUS_PROCESSING,
                'failed_at' => null,
                'error_message' => null,
            ])->save();

            $this->factory->make($providerName)->sendToAll(
                title: $notification->title,
                body: $notification->body,
                data: $notification->data ?? [],
                image: $notification->image,
                scheduledAt: $scheduledAt,
            );

            $isOneSignalScheduled = $providerName === 'onesignal' && $notification->isScheduledForFuture();

            $notification->forceFill([
                'status' => $isOneSignalScheduled
                    ? Pushify::STATUS_SCHEDULED
                    : Pushify::STATUS_SENT,
                'sent_at' => $isOneSignalScheduled ? null : now(),
                'failed_at' => null,
                'error_message' => null,
            ])->save();
        } catch (Throwable $e) {
            $notification->forceFill([
                'status' => Pushify::STATUS_FAILED,
                'failed_at' => now(),
                'error_message' => $e->getMessage(),
            ])->save();

            Log::error('Pushify notification failed.', [
                'notification_id' => $notification->id,
                'provider' => $providerName,
                'error' => $e->getMessage(),
            ]);
        }

        return $notification->refresh();
    }

    public function markScheduledAsSent(): int
    {
        return Pushify::query()
            ->where('status', Pushify::STATUS_SCHEDULED)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->update([
                'status' => Pushify::STATUS_SENT,
                'sent_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
