<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expert_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expert_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('room_id')->constrained('chat_rooms')->onDelete('cascade');
            $table->enum('status', ['active', 'cancelled', 'completed'])->default('active');
            $table->timestamps();

            $table->unique(['expert_id', 'user_id']);
            $table->index(['expert_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expert_relationships');
    }
};
