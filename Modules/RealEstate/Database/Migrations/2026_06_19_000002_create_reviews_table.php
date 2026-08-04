<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewed_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('property_id')->nullable()->constrained('properties')->nullOnDelete();
            $table->tinyInteger('rating')->unsigned()->comment('1-5 stars');
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('reviewed_id');
            $table->index('reviewer_id');
            $table->unique(['reviewer_id', 'reviewed_id'], 'unique_review_per_office');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
