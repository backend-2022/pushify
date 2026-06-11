<?php

namespace Badawy\Pushify\Services;

use Badawy\Pushify\Contracts\PushifySubscriptionsInterface;
use Badawy\Pushify\Models\PushifySubscription;
use Badawy\Pushify\Support\PushifyExternalIdGenerator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PushifySubscriptionsService implements PushifySubscriptionsInterface
{
    public function __construct(
        private readonly PushifyExternalIdGenerator $externalIdGenerator,
    ) {}

    public function addUserFor(int $userId, string $token, array $data = []): PushifySubscription
    {
        return $this->addUser(
            externalId: $this->externalIdGenerator->forUserId($userId),
            token: $token,
            data: $data,
        );
    }

    public function addUser(string $externalId, string $token, array $data = []): PushifySubscription
    {
        $appId = trim((string) config('pushify.onesignal.app_id'));
        $apiKey = trim((string) config('pushify.onesignal.api_key'));

        if ($appId === '' || $apiKey === '') {
            throw new RuntimeException('Pushify subscriptions credentials are missing.');
        }

        $payload = $this->buildUserPayload($externalId, $token, $data);

        $this->logPayload($payload);

        $response = Http::acceptJson()
            ->connectTimeout(10)
            ->timeout(30)
            ->withHeaders(['Authorization' => 'Key '.$apiKey])
            ->post("https://api.onesignal.com/apps/{$appId}/users", $payload);

        $response->throw();

        $responseData = $response->json() ?? [];
        $subscriptionId = $this->extractSubscriptionId($responseData, $token);

        return PushifySubscription::query()->updateOrCreate(
            ['device_token' => $token],
            [
                'external_id' => $externalId,
                'subscription_id' => $subscriptionId,
                'device_type' => $data['type'] ?? null,
            ],
        );
    }

    public function removeDevice(string $deviceToken): void
    {
        $subscription = PushifySubscription::query()
            ->where('device_token', $deviceToken)
            ->first();

        if ($subscription === null) {
            throw new RuntimeException('Device token not found.');
        }

        $appId = trim((string) config('pushify.onesignal.app_id'));
        $apiKey = trim((string) config('pushify.onesignal.api_key'));

        if ($appId === '' || $apiKey === '') {
            throw new RuntimeException('Pushify subscriptions credentials are missing.');
        }

        $url = sprintf(
            'https://api.onesignal.com/apps/%s/subscriptions/%s',
            rawurlencode($appId),
            rawurlencode($subscription->subscription_id),
        );

        $response = Http::acceptJson()
            ->connectTimeout(10)
            ->timeout(30)
            ->withHeaders(['Authorization' => 'Key '.$apiKey])
            ->delete($url);

        $response->throw();

        $subscription->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function buildUserPayload(string $externalId, string $token, array $data): array
    {
        $propertyKeys = [
            'tags',
            'language',
            'timezone_id',
            'country',
            'lat',
            'long',
            'ip',
            'first_active',
            'last_active',
            'test_user_name',
        ];

        $subscriptionKeys = [
            'type',
            'token',
            'enabled',
            'notification_types',
            'session_time',
            'session_count',
            'app_version',
            'device_model',
            'device_os',
            'test_type',
            'sdk',
            'web_auth',
            'web_p256',
        ];

        $properties = [];

        foreach ($propertyKeys as $key) {
            if (array_key_exists($key, $data)) {
                $properties[$key] = $data[$key];
            }
        }

        $payload = [
            'identity' => [
                'external_id' => (string) $externalId,
            ],
        ];

        if ($properties !== []) {
            $payload['properties'] = $properties;
        }

        if (isset($data['subscriptions'])) {
            $payload['subscriptions'] = $data['subscriptions'];
        } else {
            $subscription = [
                'token' => $token,
                'enabled' => $data['enabled'] ?? true,
            ];

            if (array_key_exists('type', $data)) {
                $subscription['type'] = $data['type'];
            }

            foreach ($subscriptionKeys as $key) {
                if (in_array($key, ['type', 'token', 'enabled'], true)) {
                    continue;
                }

                if (array_key_exists($key, $data)) {
                    $subscription[$key] = $data[$key];
                }
            }

            $payload['subscriptions'] = [$subscription];
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function extractSubscriptionId(array $response, string $token): string
    {
        foreach ($response['subscriptions'] ?? [] as $subscription) {
            if (! is_array($subscription)) {
                continue;
            }

            if (($subscription['token'] ?? null) === $token && isset($subscription['id'])) {
                return (string) $subscription['id'];
            }
        }

        $firstId = $response['subscriptions'][0]['id'] ?? null;

        if ($firstId === null || $firstId === '') {
            throw new RuntimeException('Subscription ID not found in provider response.');
        }

        return (string) $firstId;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function logPayload(array $payload): void
    {
        if (! (bool) config('pushify.log_payload', false)) {
            return;
        }

        Log::info('Pushify add user payload prepared.', ['payload' => $payload]);
    }
}
