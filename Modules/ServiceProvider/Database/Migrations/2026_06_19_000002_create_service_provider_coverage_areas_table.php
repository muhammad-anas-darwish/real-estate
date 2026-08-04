<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_provider_coverage_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_provider_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['service_provider_profile_id', 'city_id'], 'sp_city_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_provider_coverage_areas');
    }
};
