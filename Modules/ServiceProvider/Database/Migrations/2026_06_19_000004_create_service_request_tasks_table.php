<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_request_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')->constrained()->cascadeOnDelete();
            $table->string('task_type', 30);
            $table->json('checklist_json')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->index('task_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_request_tasks');
    }
};
