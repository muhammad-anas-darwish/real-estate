<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_account_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('ledger_accounts')->cascadeOnDelete();
            $table->string('batch_id')->index();
            $table->string('entry_type'); // debit, credit
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->decimal('balance_after', 15, 2);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('description')->nullable();
            $table->string('idempotency_key')->nullable()->unique();
            $table->json('metadata')->nullable();
            $table->timestamp('posted_at');
            $table->timestamps();

            $table->index(['account_id', 'posted_at']);
            $table->index('reference_type');
            $table->index(['reference_type', 'reference_id']);
            $table->index('entry_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_account_entries');
    }
};
