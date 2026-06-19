<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('journal_number', 50)->unique();
            $table->date('entry_date');
            $table->string('description')->nullable();
            $table->string('reference')->nullable()->index();
            $table->string('status', 20)->default('draft')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('entry_date');
        });

        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('ledger_accounts')->restrictOnDelete();
            $table->string('description')->nullable();
            $table->decimal('debit_amount', 15, 2)->default(0);
            $table->decimal('credit_amount', 15, 2)->default(0);
            $table->integer('sort_order')->default(0);
            $table->string('ledger_batch_id')->nullable()->index();
            $table->timestamps();

            $table->index(['journal_entry_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entry_lines');
        Schema::dropIfExists('journal_entries');
    }
};
