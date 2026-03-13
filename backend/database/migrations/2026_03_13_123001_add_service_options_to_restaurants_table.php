<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->boolean('service_local_enabled')->default(true)->after('active');
            $table->boolean('service_takeaway_enabled')->default(true)->after('service_local_enabled');
            $table->boolean('service_pickup_enabled')->default(true)->after('service_takeaway_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn([
                'service_local_enabled',
                'service_takeaway_enabled',
                'service_pickup_enabled',
            ]);
        });
    }
};
