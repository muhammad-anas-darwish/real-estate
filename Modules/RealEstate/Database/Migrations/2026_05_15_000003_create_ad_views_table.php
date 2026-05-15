<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_id')->constrained('ads')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('viewed_at');

            $table->index('ad_id');
            $table->index('user_id');
            $table->index('viewed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_views');
    }
};
