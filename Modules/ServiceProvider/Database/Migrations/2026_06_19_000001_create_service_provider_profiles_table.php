<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_provider_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->text('bio')->nullable();
            $table->unsignedTinyInteger('experience_years')->default(0);
            $table->string('license_number', 100)->nullable();
            $table->string('price_type', 20)->default('negotiable');
            $table->decimal('price_per_task', 10, 2)->nullable();
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_available')->default(true);
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->unsignedInteger('total_completed_tasks')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
            $table->index('is_verified');
            $table->index('is_available');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_provider_profiles');
    }
};
