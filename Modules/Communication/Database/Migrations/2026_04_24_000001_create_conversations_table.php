<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->nullable()->constrained('properties')->onDelete('cascade');
            $table->enum('type', ['property_inquiry', 'general'])->default('general');
            $table->foreignId('initiator_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('recipient_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index(['initiator_id', 'recipient_id']);
            $table->index('property_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
