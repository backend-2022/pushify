<?php

namespace Badawy\Pushify\Commands;

use Badawy\Pushify\Contracts\PushifyServiceInterface;
use Badawy\Pushify\Models\Pushify;
use Illuminate\Console\Command;

class SendScheduledPushifyNotifications extends Command
{
    protected $signature = 'pushify:send-scheduled';

    protected $description = 'Send due scheduled push notifications.';

    public function handle(PushifyServiceInterface $push): int
    {
        $provider = config('pushify.provider', 'firebase');

        if ($provider === 'onesignal') {
            $count = $push->markScheduledAsSent();
            $this->info("Marked {$count} scheduled OneSignal notifications as sent.");

            return self::SUCCESS;
        }

        Pushify::query()
            ->where('status', Pushify::STATUS_PENDING)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->orderBy('id')
            ->chunkById(100, function ($notifications) use ($push) {
                foreach ($notifications as $notification) {
                    $push->send($notification);
                }
            });

        $this->info('Scheduled push notifications processed.');

        return self::SUCCESS;
    }
}
