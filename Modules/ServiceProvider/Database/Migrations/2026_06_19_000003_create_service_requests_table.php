<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained('service_provider_profiles')->nullOnDelete();
            $table->foreignId('property_id')->nullable()->constrained('properties')->nullOnDelete();
            $table->string('service_type', 30);
            $table->string('status', 20)->default('pending');
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->text('client_notes')->nullable();
            $table->text('provider_notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('platform_fee', 10, 2)->nullable();
            $table->decimal('provider_earnings', 10, 2)->nullable();
            $table->boolean('is_paid')->default(false);
            $table->dateTime('paid_at')->nullable();
            $table->boolean('is_provider_paid')->default(false);
            $table->timestamps();

            $table->index('service_type');
            $table->index('status');
            $table->index('client_id');
            $table->index('provider_id');
            $table->index('property_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
