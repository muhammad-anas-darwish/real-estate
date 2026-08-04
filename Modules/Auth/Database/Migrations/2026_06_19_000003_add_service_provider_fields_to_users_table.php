<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_service_provider')->default(false)->after('contact_preference');
            $table->string('service_provider_type', 30)->nullable()->after('is_service_provider')->comment('photographer, lawyer, inspector, marketer, other');

            $table->index('is_service_provider');
            $table->index('service_provider_type');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_service_provider']);
            $table->dropIndex(['service_provider_type']);
            $table->dropColumn([
                'is_service_provider',
                'service_provider_type',
            ]);
        });
    }
};
