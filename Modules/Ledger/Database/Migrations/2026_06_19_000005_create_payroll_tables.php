<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_provider_profile_id')
                ->unique()
                ->constrained('service_provider_profiles')
                ->cascadeOnDelete();
            $table->string('type', 20)->index();
            $table->decimal('base_salary', 12, 2)->nullable();
            $table->decimal('per_task_rate', 12, 2)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('payroll_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained('payrolls')->cascadeOnDelete();
            $table->foreignId('service_provider_profile_id')->constrained('service_provider_profiles')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->decimal('salary_amount', 12, 2)->default(0);
            $table->integer('tasks_count')->default(0);
            $table->decimal('tasks_amount', 12, 2)->default(0);
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['period_start', 'period_end']);
            $table->index('paid_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_payments');
        Schema::dropIfExists('payrolls');
    }
};
