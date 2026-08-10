<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            $this->upMysql();
        } else {
            $this->upSqlite();
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            $this->downMysql();
        } else {
            $this->downSqlite();
        }
    }

    private function upMysql(): void
    {
        Schema::table('property_viewings', function (Blueprint $table) {
            $table->string('type')->default('viewing')->after('id')->index();
            $table->string('contact_method', 32)->nullable()->after('viewing_type');
            $table->nullableMorphs('followable');
        });

        DB::statement('ALTER TABLE property_viewings MODIFY property_id BIGINT UNSIGNED NULL');

        Schema::table('property_viewings', function (Blueprint $table) {
            $table->dropForeign(['property_id']);
        });
    }

    private function downMysql(): void
    {
        Schema::table('property_viewings', function (Blueprint $table) {
            $table->foreign('property_id')->references('id')->on('properties')->cascadeOnDelete();
        });

        DB::statement('ALTER TABLE property_viewings MODIFY property_id BIGINT UNSIGNED NOT NULL');

        Schema::table('property_viewings', function (Blueprint $table) {
            $table->dropMorphs('followable');
            $table->dropColumn(['type', 'contact_method']);
        });
    }

    private function upSqlite(): void
    {
        Schema::table('property_viewings', function (Blueprint $table) {
            $table->string('type')->default('viewing')->after('id');
            $table->string('contact_method', 32)->nullable()->after('viewing_type');
            $table->nullableMorphs('followable');
        });
    }

    private function downSqlite(): void
    {
        Schema::table('property_viewings', function (Blueprint $table) {
            $table->dropMorphs('followable');
            $table->dropColumn(['type', 'contact_method']);
        });
    }
};
