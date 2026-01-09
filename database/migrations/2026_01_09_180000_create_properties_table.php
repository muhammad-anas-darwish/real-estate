<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->string('country');
            $table->string('city');
            $table->decimal('longitude', 10, 8);
            $table->decimal('latitude', 10, 8);
            $table->integer('rooms');
            $table->integer('bathrooms');
            $table->decimal('total_area', 10, 2);
            $table->foreignId('publisher_id')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->text('detailed_info');
            $table->decimal('price', 15, 2);
            $table->json('photos');
            $table->string('main_image');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};