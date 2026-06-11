<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = (string) config('pushify.users.table', 'users');
        $columnName = (string) config('pushify.users.external_id_column', 'pushify_external_id');

        if (! Schema::hasTable($tableName)) {
            throw new \RuntimeException(
                "Pushify migration aborted: the [{$tableName}] table does not exist. ".
                'Create your users table first, then run migrations again.'
            );
        }

        if (Schema::hasColumn($tableName, $columnName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($columnName) {
            $table->string($columnName, 20)->nullable()->unique();
        });
    }

    public function down(): void
    {
        $tableName = (string) config('pushify.users.table', 'users');
        $columnName = (string) config('pushify.users.external_id_column', 'pushify_external_id');

        if (! Schema::hasTable($tableName)) {
            return;
        }

        if (! Schema::hasColumn($tableName, $columnName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($columnName) {
            $table->dropColumn($columnName);
        });
    }
};
