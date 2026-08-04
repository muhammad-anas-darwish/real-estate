<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_id')->constrained('ads')->cascadeOnDelete();
            $table->string('file_path');
            $table->enum('media_type', ['video', 'image'])->default('image');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('sort_order');
            $table->index(['ad_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_media');
    }
};
