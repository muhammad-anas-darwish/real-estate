<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_cards', function (Blueprint $table) {
            $table->id();

            $table->foreignId('property_id')
                ->constrained('properties')
                ->cascadeOnDelete();

            $table->foreignId('owner_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('tenant_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('external_tenant_name')->nullable();
            $table->string('external_tenant_phone', 32)->nullable();
            $table->string('external_tenant_email')->nullable();
            $table->text('external_tenant_id_notes')->nullable();

            $table->date('start_date');
            $table->date('end_date');

            $table->text('terms')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_renewable')->default(false);

            $table->string('status')->default('active');

            $table->timestamp('ended_at')->nullable();
            $table->text('end_reason')->nullable();
            $table->foreignId('ended_by')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->timestamp('renewed_at')->nullable();
            $table->unsignedInteger('renewal_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['property_id', 'status']);
            $table->index(['owner_id', 'status']);
            $table->index(['tenant_user_id']);
            $table->index(['end_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_cards');
    }
};
