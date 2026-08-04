<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->boolean('is_physically_verified')->default(false)->after('rejection_reason');
            $table->dateTime('inspection_requested_at')->nullable()->after('is_physically_verified');
            $table->dateTime('inspection_completed_at')->nullable()->after('inspection_requested_at');
            $table->unsignedTinyInteger('inspection_score')->nullable()->after('inspection_completed_at');
            $table->json('inspection_report')->nullable()->after('inspection_score');
            $table->index('is_physically_verified');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropIndex(['is_physically_verified']);
            $table->dropColumn([
                'is_physically_verified',
                'inspection_requested_at',
                'inspection_completed_at',
                'inspection_score',
                'inspection_report',
            ]);
        });
    }
};
