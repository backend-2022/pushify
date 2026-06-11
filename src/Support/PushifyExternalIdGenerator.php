<?php

namespace Badawy\Pushify\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class PushifyExternalIdGenerator
{
    public function forUserId(int $userId): string
    {
        $table = (string) config('pushify.users.table', 'users');
        $column = (string) config('pushify.users.external_id_column', 'pushify_external_id');

        $user = DB::table($table)->where('id', $userId)->first();

        if ($user === null) {
            throw new RuntimeException("User [{$userId}] was not found in [{$table}].");
        }

        $existing = $user->{$column} ?? null;

        if (is_string($existing) && $existing !== '') {
            return $existing;
        }

        $externalId = $this->generate($userId);

        DB::table($table)
            ->where('id', $userId)
            ->update([$column => $externalId]);

        return $externalId;
    }

    /**
     * @param  array<int, int|string>  $userIds
     * @return array<int, string>
     */
    public function forUserIds(array $userIds): array
    {
        return array_map(
            fn (int|string $userId): string => $this->forUserId((int) $userId),
            $userIds,
        );
    }

    public function generate(int $userId): string
    {
        return strtoupper(Str::random(8)).'_'.$userId;
    }
}
