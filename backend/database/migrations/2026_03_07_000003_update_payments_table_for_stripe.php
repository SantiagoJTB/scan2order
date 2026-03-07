<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('provider')->default('none')->after('method');
            $table->string('provider_payment_id')->nullable()->unique()->after('provider');
            $table->string('currency', 3)->default('eur')->after('amount');
            $table->string('status')->default('pending')->after('currency');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique('payments_provider_payment_id_unique');
            $table->dropColumn(['provider', 'provider_payment_id', 'currency', 'status']);
        });
    }
};
