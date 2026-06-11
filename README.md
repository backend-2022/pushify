# Pushify

[![Latest Version on Packagist](https://img.shields.io/packagist/v/badawy24/pushify.svg?style=flat-square)](https://packagist.org/packages/badawy24/pushify) [![PHP Version](https://img.shields.io/badge/php-%5E8.2-blue?style=flat-square)](https://www.php.net) [![Laravel Version](https://img.shields.io/badge/laravel-%5E12.0-red?style=flat-square)](https://laravel.com) [![License: MIT](https://img.shields.io/badge/license-MIT-green?style=flat-square)](LICENSE)

> Laravel backend package for sending push notifications through one selected provider at a time, with OneSignal device subscription management built in.

Supported providers:

| Provider | Scheduling | Subscriptions |
|---|---|---|
| 🔥 Firebase Cloud Messaging (FCM) | Via scheduled command | — |
| 📣 OneSignal | Native `send_after` | ✅ Device register & logout |

---

## Project Structure

```text
pushify/
├── config/
│   └── pushify.php
├── database/
│   └── migrations/
│       ├── 2026_01_01_000000_create_pushify_notifications_table.php
│       ├── 2026_01_02_000000_create_pushify_subscriptions_table.php
│       └── 2026_01_03_000000_add_pushify_external_id_to_users_table.php
├── routes/
│   └── pushify.php
├── src/
│   ├── Commands/
│   │   └── SendScheduledPushifyNotifications.php
│   ├── Concerns/
│   │   └── HasPushifyExternalId.php
│   ├── Contracts/
│   │   ├── PushifyProviderInterface.php
│   │   ├── PushifyServiceInterface.php
│   │   └── PushifySubscriptionsInterface.php
│   ├── Factories/
│   │   └── PushifyProviderFactory.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── PushifyController.php
│   │   ├── Requests/
│   │   │   └── StorePushifyRequest.php
│   │   └── Resources/
│   │       └── PushifyResource.php
│   ├── Models/
│   │   ├── Pushify.php
│   │   └── PushifySubscription.php
│   ├── Providers/
│   │   ├── FirebaseProvider.php
│   │   └── OneSignalProvider.php
│   ├── Services/
│   │   ├── FirebaseService.php
│   │   ├── OneSignalService.php
│   │   ├── PushifyService.php
│   │   └── PushifySubscriptionsService.php
│   ├── Support/
│   │   └── PushifyExternalIdGenerator.php
│   └── PushifyServiceProvider.php
├── stubs/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── PushifyController.stub
│   │   ├── Requests/
│   │   │   └── StorePushifyRequest.stub
│   │   └── Resources/
│   │       └── PushifyResource.stub
│   └── routes/
│       └── pushify.stub
└── composer.json
```

---

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Publish](#publish)
- [Migration](#migration)
- [Configuration](#configuration)
- [Routes](#routes)
- [Usage](#usage)
  - [Notifications](#notifications)
  - [Subscriptions (OneSignal)](#subscriptions-onesignal)
- [Notification Statuses](#notification-statuses)
- [Scheduled Command](#scheduled-command)
- [Customizing the HTTP Layer](#customizing-the-http-layer)
- [Adding a Custom Provider](#adding-a-custom-provider)
- [Store Endpoint Payload](#store-endpoint-payload)
- [Response Structure](#response-structure)
- [Authors](#authors)
- [License](#license)

---

## Requirements

- PHP >= 8.2
- Laravel >= 12.0
- OpenSSL PHP extension *(required for Firebase JWT signing — no external Google SDK needed)*
- A `users` table *(required before running Pushify migrations)*

---

## Installation

```bash
composer require badawy24/pushify
```

Then clear the cache:

```bash
php artisan optimize:clear
```

---

## Publish

### Publish everything at once

```bash
php artisan vendor:publish --tag=pushify
```

This publishes only the files you are expected to edit:

```text
config/pushify.php
routes/pushify.php
app/Http/Controllers/Pushify/PushifyController.php
app/Http/Requests/Pushify/StorePushifyRequest.php
app/Http/Resources/Pushify/PushifyResource.php
```

> Core services, providers, factories, models, commands, and contracts stay inside the package and are never published.

### Publish separately

```bash
# Config only
php artisan vendor:publish --tag=pushify-config

# Routes only
php artisan vendor:publish --tag=pushify-routes

# Controller, Request, Resource
php artisan vendor:publish --tag=pushify-http
```

---

## Migration

> **Important:** Your `users` table must exist before running migrations. If it does not, the migration will abort with a clear error message.

```bash
php artisan migrate
```

### Tables created / modified

**`pushify_notifications`** — notification log:

```text
id, title, body, image, data, scheduled_at,
status, sent_at, failed_at, error_message,
created_at, updated_at
```

**`pushify_subscriptions`** — registered devices:

```text
id, external_id, device_token, subscription_id,
device_type, created_at, updated_at
```

**`users`** — column added:

```text
pushify_external_id   (nullable, unique)   e.g. AGFGFFGY_1
```

The `pushify_external_id` is generated automatically on first use: `8 random chars` + `_` + `user_id`.

---

## Configuration

Published config file: `config/pushify.php`

Only one provider is active at a time, selected from `.env`.

### 🔥 Firebase

```env
PUSHIFY_PROVIDER=firebase
FIREBASE_CREDENTIALS=storage/firebase/firebase.json
```

Place your Firebase service account JSON file at `storage/firebase/firebase.json`.

Firebase sends to a topic — default is `all`. Configure it in the published config:

```php
'firebase' => [
    'topic' => 'all',
],
```

> Your mobile app must subscribe devices to the same topic.

### 📣 OneSignal

```env
PUSHIFY_PROVIDER=onesignal
ONESIGNAL_APP_ID=your-app-id
ONESIGNAL_API_KEY=your-api-key
ONESIGNAL_API_URL=https://api.onesignal.com/notifications
```

OneSignal `sendToAll` sends to `included_segments => ['All']`.

### Users (external ID)

```env
PUSHIFY_USERS_TABLE=users
PUSHIFY_EXTERNAL_ID_COLUMN=pushify_external_id
```

### Optional

```env
# Log full request payload — disable in production
PUSHIFY_LOG_PAYLOAD=false

# Disable package routes if you prefer to define your own
PUSHIFY_ROUTES_ENABLED=true

# Change the route prefix (default: pushify)
PUSHIFY_ROUTE_PREFIX=pushify
```

---

## Routes

The package registers these routes automatically:

```text
GET  /pushify                  List all notifications (paginated)
POST /pushify                  Create and send
GET  /pushify/{pushify}        Show one
POST /pushify/{pushify}/send   Send an existing notification
```

Check registered routes:

```bash
php artisan route:list | grep pushify
```

### Adding Auth Middleware

After publishing, open `routes/pushify.php` and update the middleware:

```php
Route::prefix(config('pushify.routes.prefix', 'pushify'))
    ->middleware(['api', 'auth:sanctum'])
    ->group(function () {
        // routes...
    });
```

---

## Usage

The package exposes two separate interfaces:

| Interface | Purpose |
|---|---|
| `PushifyServiceInterface` | Send notifications |
| `PushifySubscriptionsInterface` | Register & remove devices *(OneSignal only)* |

Both are bound automatically — **no manual binding required**.

---

### Notifications

#### Inject the service

```php
use Badawy\Pushify\Contracts\PushifyServiceInterface;
```

#### Send to all

```php
$notification = $push->sendToAll(
    title: 'New offer',
    body: 'Check our latest offers now',
    data: [
        'type'     => 'offer',
        'offer_id' => 15,
    ],
    image: 'https://example.com/image.jpg',
    scheduledAt: null,
);
```

#### Send to a specific user

Use `sendToUserById()` with your local `user_id` — the package resolves `pushify_external_id` from the `users` table automatically:

```php
$notification = $push->sendToUserById(
    userIds: $user->id,
    title: 'Order updated',
    body: 'Your order status has changed',
    data: [
        'type'     => 'order_status_updated',
        'order_id' => 15,
    ],
);
```

OneSignal delivers to **all devices** registered under that user's `external_id`.

You can also pass `external_id` strings directly via `sendToUser()`:

```php
$notification = $push->sendToUser(
    userIds: [$user->pushifyExternalId()],
    title: 'Order updated',
    body: 'Your order status has changed',
    data: ['type' => 'order_status_updated'],
);
```

#### Schedule a notification

```php
$notification = $push->sendToAll(
    title: 'Upcoming sale',
    body: 'Our sale starts in one hour',
    data: ['type' => 'sale'],
    image: null,
    scheduledAt: now()->addHour()->toDateTimeString(),
);
```

| Provider | Behavior |
|---|---|
| 🔥 Firebase | Saved as `pending` — sent by the command when `scheduled_at <= now()` |
| 📣 OneSignal | Submitted immediately with native `send_after` — OneSignal handles the delay |

#### Create only (without sending)

```php
$notification = $push->create([
    'title'        => 'Draft notification',
    'body'         => 'Notification body',
    'data'         => ['type' => 'general'],
    'image'        => null,
    'scheduled_at' => null,
]);
```

#### Send an existing notification

```php
use Badawy\Pushify\Models\Pushify;

$notification = Pushify::findOrFail($id);
$notification = $push->send($notification);
```

#### `PushifyServiceInterface` methods

```php
public function create(array $payload): Pushify;

public function sendToAll(
    string $title,
    string $body,
    array $data = [],
    ?string $image = null,
    ?string $scheduledAt = null
): Pushify;

public function sendToUser(
    array|string $userIds,
    string $title,
    string $body,
    array $data = [],
    ?string $image = null,
    ?string $scheduledAt = null
): Pushify;

public function sendToUserById(
    array|int $userIds,
    string $title,
    string $body,
    array $data = [],
    ?string $image = null,
    ?string $scheduledAt = null
): Pushify;

public function send(Pushify $notification): Pushify;

public function markScheduledAsSent(): int;
```

---

### Subscriptions (OneSignal)

> Requires `PUSHIFY_PROVIDER=onesignal`.

#### Setup — add trait to your User model

```php
use Badawy\Pushify\Concerns\HasPushifyExternalId;

class User extends Authenticatable
{
    use HasPushifyExternalId;
}
```

#### Inject the service

```php
use Badawy\Pushify\Contracts\PushifySubscriptionsInterface;
```

#### Full flow

```php
class AuthController extends Controller
{
    public function __construct(
        private readonly PushifySubscriptionsInterface $subscriptions,
        private readonly PushifyServiceInterface $pushify,
    ) {}

    // 1. Login — register device
    public function registerDevice(Request $request)
    {
        $subscription = $this->subscriptions->addUserFor(
            userId: $request->user()->id,
            token: $request->input('device_token'),
            data: ['type' => 'Android'],
        );

        return response()->json($subscription);
    }

    // 2. Send notification (from anywhere in your app)
    public function notifyUser(int $userId)
    {
        $this->pushify->sendToUserById(
            userIds: $userId,
            title: 'Hello',
            body: 'You have a new notification',
            data: ['type' => 'general'],
        );
    }

    // 3. Logout — remove device
    public function logout(Request $request)
    {
        $this->subscriptions->removeDevice($request->input('device_token'));

        // ... your logout logic
    }
}
```

#### What happens internally

| Step | Method | Action |
|---|---|---|
| Login | `addUserFor($userId, $token)` | Generates `pushify_external_id` on `users`, registers device on OneSignal, saves `device_token` + `subscription_id` in `pushify_subscriptions` |
| Notify | `sendToUserById($userId, ...)` | Resolves `pushify_external_id` from `users`, sends via OneSignal to all user devices |
| Logout | `removeDevice($deviceToken)` | Deletes subscription from OneSignal, removes row from `pushify_subscriptions` |

#### `PushifySubscriptionsInterface` methods

```php
// Register device using local user_id (recommended)
public function addUserFor(int $userId, string $token, array $data = []): PushifySubscription;

// Register device using a manual external_id
public function addUser(string $externalId, string $token, array $data = []): PushifySubscription;

// Remove device on logout using device_token
public function removeDevice(string $deviceToken): void;
```

#### Optional `$data` fields for `addUserFor` / `addUser`

| Field | Description |
|---|---|
| `type` | Device type, e.g. `Android`, `iOS` |
| `language` | User language, e.g. `ar` |
| `timezone_id` | e.g. `Africa/Cairo` |
| `country` | e.g. `EG` |
| `tags` | Key-value tags array |
| `device_model` | e.g. `iPhone 15` |
| `device_os` | e.g. `iOS 18` |
| `app_version` | e.g. `1.0.0` |

---

### Quick test via Tinker

```bash
php artisan tinker
```

```php
// Register device
app(\Badawy\Pushify\Contracts\PushifySubscriptionsInterface::class)
    ->addUserFor(1, 'your-fcm-token', ['type' => 'Android']);

// Send notification
app(\Badawy\Pushify\Contracts\PushifyServiceInterface::class)
    ->sendToUserById(1, 'Hello', 'Test from Tinker', ['type' => 'test']);

// Logout device
app(\Badawy\Pushify\Contracts\PushifySubscriptionsInterface::class)
    ->removeDevice('your-fcm-token');
```

---

## Notification Statuses

| Status | Meaning |
|---|---|
| `pending` | Stored, not sent yet |
| `processing` | Currently being dispatched to the provider |
| `scheduled` | Submitted to OneSignal with a future `send_after` |
| `sent` | Successfully delivered to the provider |
| `failed` | Failed — see `error_message` column in the database |

---

## Scheduled Command

```bash
php artisan pushify:send-scheduled
```

| Provider | What it does |
|---|---|
| 🔥 Firebase | Sends all `pending` notifications where `scheduled_at <= now()` |
| 📣 OneSignal | Marks all `scheduled` notifications where `scheduled_at <= now()` as `sent` locally — OneSignal already delivered them |

### Add to Laravel Scheduler

In `routes/console.php`:

```php
Schedule::command('pushify:send-scheduled')->everyMinute();
```

Or via cron:

```bash
* * * * * php /path/to/project/artisan pushify:send-scheduled >> /dev/null 2>&1
```

---

## Customizing the HTTP Layer

After publishing with `--tag=pushify-http`, you can freely edit:

| File | Purpose |
|---|---|
| `app/Http/Controllers/Pushify/PushifyController.php` | Request handling & response |
| `app/Http/Requests/Pushify/StorePushifyRequest.php` | Validation rules & authorization |
| `app/Http/Resources/Pushify/PushifyResource.php` | JSON output shape |

The published controller injects `PushifyServiceInterface` — extend or replace any logic without touching the package internals.

---

## Adding a Custom Provider

**Step 1 — Create your provider class:**

```php
namespace App\Pushify\Providers;

use Badawy\Pushify\Contracts\PushifyProviderInterface;

class CustomProvider implements PushifyProviderInterface
{
    public function sendToAll(
        string $title,
        string $body,
        array $data = [],
        ?string $image = null,
        ?string $scheduledAt = null
    ): array {
        return ['success' => true];
    }

    public function sendToUser(
        array|string $userIds,
        string $title,
        string $body,
        array $data = [],
        ?string $image = null,
        ?string $scheduledAt = null
    ): array {
        return ['success' => true];
    }
}
```

**Step 2 — Register it in `config/pushify.php`:**

```php
'providers' => [
    'firebase'  => \Badawy\Pushify\Providers\FirebaseProvider::class,
    'onesignal' => \Badawy\Pushify\Providers\OneSignalProvider::class,
    'custom'    => \App\Pushify\Providers\CustomProvider::class,
],
```

**Step 3 — Activate it in `.env`:**

```env
PUSHIFY_PROVIDER=custom
```

---

## Store Endpoint Payload

`POST /pushify`

```json
{
    "title": "New offer",
    "body": "Check our latest offers",
    "image": "https://example.com/image.jpg",
    "data": {
        "type": "offer",
        "offer_id": 15
    },
    "scheduled_at": null
}
```

Scheduled example:

```json
{
    "title": "Scheduled offer",
    "body": "This will be sent later",
    "image": null,
    "data": { "type": "offer" },
    "scheduled_at": "2026-06-01 09:00:00"
}
```

---

## Response Structure

All endpoints return a consistent JSON envelope:

```json
{
    "data": {
        "id": 1,
        "title": "New offer",
        "body": "Check our latest offers",
        "image": "https://example.com/image.jpg",
        "data": { "type": "offer", "offer_id": "15" },
        "scheduled_at": null,
        "status": "sent",
        "sent_at": "2026-01-01T12:00:00+00:00",
        "failed_at": null,
        "created_at": "2026-01-01T11:59:00+00:00",
        "updated_at": "2026-01-01T12:00:00+00:00"
    }
}
```

---

## Authors

[Badawy](https://github.com/Badawy24) · [Hassan](https://github.com/hassanmostfa)

---

## License

MIT
