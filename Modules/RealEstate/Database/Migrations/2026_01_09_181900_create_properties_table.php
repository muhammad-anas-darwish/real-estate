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

            // Basic Information
            $table->string('name');
            $table->text('description');

            // Location
            $table->string('country');
            $table->string('city');
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();

            // Property Details
            $table->unsignedInteger('rooms')->default(0);
            $table->unsignedInteger('bathrooms')->default(0);
            $table->decimal('area', 10, 2)->comment('Total area in square meters');

            // Detailed Information
            $table->text('detailed_info')->nullable();

            // Price
            $table->decimal('price', 15, 2);
            $table->string('currency', 3)->default('USD');

            // Publisher and Approval
            $table->foreignId('publisher_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();

            // Status
            $table->enum('status', ['pending', 'approved', 'rejected', 'sold'])->default('pending');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['country', 'city']);
            $table->index('status');
            $table->index('price');
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
