<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pushify_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('external_id');
            $table->text('device_token');
            $table->string('subscription_id');
            $table->string('device_type')->nullable();
            $table->timestamps();

            $table->unique('device_token');
            $table->unique('subscription_id');
            $table->index('external_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pushify_subscriptions');
    }
};
