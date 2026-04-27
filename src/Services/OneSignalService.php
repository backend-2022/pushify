<?php

namespace Badawy\Pushify\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OneSignalService
{
    public function sendToAll(string $title, string $body, array $data = [], ?string $image = null, ?string $sendAfter = null): array
    {
        $appId = trim((string) config('pushify.onesignal.app_id'));
        $apiKey = trim((string) config('pushify.onesignal.api_key'));
        $apiUrl = rtrim((string) config('pushify.onesignal.api_url'), '/');

        if ($appId === '' || $apiKey === '') {
            throw new RuntimeException('OneSignal credentials are missing.');
        }

        $payload = [
            'app_id' => $appId,
            'target_channel' => 'push',
            'included_segments' => ['All'],
            'headings' => ['en' => $title, 'ar' => $title],
            'contents' => ['en' => $body, 'ar' => $body],
        ];

        if ($data !== []) {
            $payload['data'] = $data;
        }

        if ($image) {
            $payload['global_image'] = $image;
            $payload['big_picture'] = $image;
            $payload['large_icon'] = $image;
            $payload['chrome_web_image'] = $image;
            $payload['ios_attachments'] = ['notification_image' => $image];
            $payload['mutable_content'] = true;
            $payload['data']['image'] = $image;
        }

        if ($sendAfter) {
            $payload['send_after'] = $sendAfter;
        }

        $this->logPayload($payload);

        $response = Http::acceptJson()
            ->connectTimeout(10)
            ->timeout(30)
            ->withHeaders(['Authorization' => 'Key '.$apiKey])
            ->post($apiUrl, $payload);

        $response->throw();

        return $response->json() ?? [];
    }

    private function logPayload(array $payload): void
    {
        if (! (bool) config('pushify.log_payload', false)) {
            return;
        }

        $payload['app_id'] = isset($payload['app_id']) ? $this->mask((string) $payload['app_id']) : null;

        Log::info('OneSignal push payload prepared.', ['payload' => $payload]);
    }

    private function mask(string $value): string
    {
        $length = strlen($value);
        if ($length <= 8) {
            return str_repeat('*', $length);
        }

        return substr($value, 0, 3).str_repeat('*', $length - 6).substr($value, -3);
    }
}
