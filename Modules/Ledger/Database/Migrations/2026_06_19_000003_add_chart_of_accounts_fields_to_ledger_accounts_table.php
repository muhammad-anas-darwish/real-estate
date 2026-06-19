<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ledger_accounts', function (Blueprint $table) {
            $table->string('account_category', 20)->nullable()->after('type')->index();
            $table->string('account_number', 50)->nullable()->after('account_category')->index();
            $table->integer('sort_order')->default(0)->after('account_number');
            $table->text('description')->nullable()->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('ledger_accounts', function (Blueprint $table) {
            $table->dropColumn(['account_category', 'account_number', 'sort_order', 'description']);
        });
    }
};
