<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_rooms', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['private', 'group'])->default('private');
            $table->string('name')->nullable();
            $table->foreignId('property_id')->nullable()->constrained('properties')->onDelete('set null');
            $table->timestamps();

            $table->index('type');
            $table->index('property_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_rooms');
    }
};
