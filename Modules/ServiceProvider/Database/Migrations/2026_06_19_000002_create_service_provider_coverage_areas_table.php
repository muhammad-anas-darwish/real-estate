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
            $table->unsignedBigInteger('service_provider_profile_id');
            $table->foreignId('city_id')->constrained();

            $table->foreign('service_provider_profile_id', 'spca_profile_fk')
                ->references('id')->on('service_provider_profiles')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['service_provider_profile_id', 'city_id'], 'spca_profile_city_uniq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_provider_coverage_areas');
    }
};
