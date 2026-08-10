<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('user_folders')->nullOnDelete();
            $table->string('name');
            $table->string('folder_type')->default('regular');
            $table->foreignId('source_id')->nullable();
            $table->string('source_type')->nullable();
            $table->boolean('is_protected')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'parent_id']);
            $table->index(['source_type', 'source_id']);
            $table->unique(['parent_id', 'name', 'user_id'], 'unique_folder_name_per_parent');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_folders');
    }
};
