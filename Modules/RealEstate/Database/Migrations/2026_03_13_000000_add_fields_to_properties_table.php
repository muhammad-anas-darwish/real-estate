<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            // Drop index explicitly if it exists
            $table->dropIndex(['country', 'city']);

            if (Schema::hasColumn('properties', 'country')) {
                $table->dropColumn('country');
            }
            if (Schema::hasColumn('properties', 'city')) {
                $table->dropColumn('city');
            }

            $table->foreignId('country_id')->nullable()->constrained('countries')->onDelete('set null');
            $table->foreignId('city_id')->nullable()->constrained('cities')->onDelete('set null');

            $table->enum('property_type', ['apartment', 'house', 'villa', 'land', 'commercial', 'office', 'warehouse', 'other'])->nullable();
            $table->enum('type_of_contract', ['sale', 'rent'])->default('sale');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropForeign(['country_id']);
            $table->dropForeign(['city_id']);
            $table->dropColumn(['country_id', 'city_id', 'property_type', 'type_of_contract']);
        });
    }
};
