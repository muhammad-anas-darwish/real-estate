<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stripe_events', function (Blueprint $table) {
            $table->id();
            $table->string('stripe_event_id')->unique();
            $table->string('type');
            $table->string('status')->default('pending')->comment('pending, processing, processed, failed');
            $table->json('payload')->nullable();
            $table->string('error_message')->nullable();
            $table->timestamps();

            $table->index('stripe_event_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stripe_events');
    }
};
