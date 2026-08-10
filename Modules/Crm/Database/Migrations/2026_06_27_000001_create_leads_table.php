<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trader_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('phone', 32)->index();
            $table->string('email')->nullable()->index();
            $table->string('source', 32);
            $table->string('status', 32)->default('new')->index();
            $table->text('lost_reason')->nullable();
            $table->timestamp('status_changed_at')->nullable();
            $table->timestamp('archived_at')->nullable()->index();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['trader_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
