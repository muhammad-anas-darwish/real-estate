<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            // Type discriminator: banner | sponsored
            $table->string('type')->default('banner')->after('ad_group_id');
            $table->index('type');

            // Sponsored ad fields (nullable for banner ads)
            $table->foreignId('user_id')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->decimal('amount_paid', 10, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('payment_method')->nullable(); // stripe | balance
            $table->string('payment_reference')->nullable(); // batch_id from ledger
            $table->string('pricing_tier')->nullable(); // basic | standard | premium
            $table->string('sponsor_duration')->nullable(); // 7_days | 14_days | 30_days
            $table->string('target_url')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            // Make ad_group_id nullable for sponsored ads (they may not belong to a group)
            $table->foreignId('ad_group_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->dropColumn([
                'type', 'user_id', 'amount_paid', 'currency',
                'payment_method', 'payment_reference', 'pricing_tier',
                'sponsor_duration', 'target_url', 'starts_at', 'ends_at',
            ]);
        });
    }
};
