<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('publisher_type', 20)->nullable()->after('status')->comment('individual or office');
            $table->string('phone', 20)->nullable()->after('publisher_type');
            $table->string('website_url', 255)->nullable()->after('phone');
            $table->json('social_links')->nullable()->after('website_url');
            $table->text('description')->nullable()->after('social_links');
            $table->boolean('is_verified')->default(false)->after('description');
            $table->integer('employees_count')->default(0)->after('is_verified');
            $table->string('contact_preference', 20)->default('chat')->after('employees_count');
            $table->decimal('average_rating', 3, 2)->default(0)->after('contact_preference');

            $table->index('publisher_type');
            $table->index('is_verified');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['publisher_type']);
            $table->dropIndex(['is_verified']);
            $table->dropColumn([
                'publisher_type',
                'phone',
                'website_url',
                'social_links',
                'description',
                'is_verified',
                'employees_count',
                'contact_preference',
                'average_rating',
            ]);
        });
    }
};
