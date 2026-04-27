<?php

namespace Badawy\Pushify\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class FirebaseService
{
    public function sendToTopic(string $title, string $body, array $data = [], ?string $image = null, ?string $topic = null): array
    {
        $credentials = $this->credentials();
        $projectId = $credentials['project_id'] ?? null;

        if (! $projectId) {
            throw new RuntimeException('Firebase project_id is missing.');
        }

        $topic ??= (string) config('pushify.firebase.topic', 'all');

        $messageData = array_map(static fn ($value) => is_scalar($value) ? (string) $value : json_encode($value), $data);

        if ($image) {
            $messageData['image'] = $image;
        }

        $payload = [
            'message' => [
                'topic' => $topic,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $messageData,
            ],
        ];

        if ($image) {
            $payload['message']['android']['notification']['image'] = $image;
            $payload['message']['apns']['payload']['aps']['mutable-content'] = 1;
            $payload['message']['apns']['fcm_options']['image'] = $image;
        }

        if ((bool) config('pushify.log_payload', false)) {
            Log::info('Firebase push payload prepared.', ['payload' => $payload]);
        }

        $response = Http::acceptJson()
            ->withToken($this->accessToken())
            ->timeout(30)
            ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", $payload);

        $response->throw();

        return $response->json() ?? [];
    }

    private function credentials(): array
    {
        $path = (string) config('pushify.firebase.credentials');

        if ($path === '') {
            throw new RuntimeException('Firebase credentials path is missing.');
        }

        $fullPath = str_starts_with($path, '/') ? $path : base_path($path);

        if (! is_file($fullPath)) {
            $storagePath = storage_path($path);
            $fullPath = is_file($storagePath) ? $storagePath : $fullPath;
        }

        if (! is_file($fullPath)) {
            throw new RuntimeException("Firebase credentials file not found: {$path}");
        }

        $credentials = json_decode((string) file_get_contents($fullPath), true);

        if (! is_array($credentials)) {
            throw new RuntimeException('Invalid Firebase credentials JSON.');
        }

        return $credentials;
    }

    private function accessToken(): string
    {
        return Cache::remember(config('pushify.firebase.token_cache_key'), now()->addMinutes(50), function () {
            $credentials = $this->credentials();

            if (empty($credentials['private_key']) || empty($credentials['client_email'])) {
                throw new RuntimeException('Firebase credentials must contain client_email and private_key.');
            }

            if (! extension_loaded('openssl') || ! function_exists('openssl_sign')) {
                throw new RuntimeException('Firebase JWT signing requires the OpenSSL PHP extension.');
            }

            $now = time();
            $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claim = $this->base64UrlEncode(json_encode([
                'iss' => $credentials['client_email'],
                'scope' => config('pushify.firebase.scope'),
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]));

            $signature = '';
            $signed = openssl_sign($header.'.'.$claim, $signature, $credentials['private_key'], 'sha256WithRSAEncryption');

            if ($signed !== true) {
                throw new RuntimeException('Unable to sign Firebase JWT using OpenSSL.');
            }

            $jwt = $header.'.'.$claim.'.'.$this->base64UrlEncode($signature);

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            $response->throw();

            return (string) $response->json('access_token');
        });
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
