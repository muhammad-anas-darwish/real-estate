<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expert_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('expert_type', ['photographer', 'lawyer', 'consultant']);
            $table->text('message')->nullable();
            $table->enum('status', ['pending', 'resolved'])->default('pending');
            $table->timestamps();

            $table->index(['user_id', 'expert_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expert_requests');
    }
};
