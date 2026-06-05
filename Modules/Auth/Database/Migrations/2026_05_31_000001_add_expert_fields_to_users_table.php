<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('expert_type', ['photographer', 'lawyer', 'consultant'])->nullable()->after('status');
            $table->integer('max_users')->default(-1)->after('expert_type');
            $table->boolean('is_expert')->default(false)->after('max_users');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['expert_type', 'max_users', 'is_expert']);
        });
    }
};
