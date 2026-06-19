<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_discounts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('type')->comment('percentage or fixed');
            $table->decimal('value', 15, 2)->comment('Discount amount or percentage');
            $table->foreignId('plan_id')->nullable()->constrained('subscription_plans')->nullOnDelete()->comment('Null means global discount');
            $table->unsignedInteger('max_uses')->nullable()->comment('Null means unlimited');
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('code');
            $table->index('is_active');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_discounts');
    }
};
