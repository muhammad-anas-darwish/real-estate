<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_group_id')->nullable()->constrained('ad_groups')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('media_type', ['video', 'image'])->default('image');
            $table->string('external_url')->nullable();
            $table->foreignId('property_id')->nullable()->constrained('properties')->nullOnDelete();
            $table->enum('status', ['draft', 'active', 'paused', 'archived'])->default('draft');
            $table->boolean('is_default')->default(false);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('is_default');
            $table->index('start_date');
            $table->index('end_date');
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ads');
    }
};
