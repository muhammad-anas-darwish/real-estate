<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_commission_configs', function (Blueprint $table) {
            $table->id();
            $table->string('service_type', 30)->unique();
            $table->string('commission_type', 20)->default('percentage');
            $table->decimal('commission_value', 8, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_commission_configs');
    }
};
