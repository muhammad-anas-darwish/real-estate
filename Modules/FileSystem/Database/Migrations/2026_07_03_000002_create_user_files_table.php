<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('folder_id')->constrained('user_folders')->cascadeOnDelete();
            $table->string('name');
            $table->string('file_type')->default('text');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->text('content')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'folder_id']);
            $table->index(['user_id', 'file_type']);
            $table->unique(['folder_id', 'name', 'user_id'], 'unique_file_name_per_folder');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_files');
    }
};
