<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->string('publisher_type', 20)->nullable()->after('sort_order')->comment('individual, office, or null for both');

            $table->index('publisher_type');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropIndex(['publisher_type']);
            $table->dropColumn('publisher_type');
        });
    }
};
