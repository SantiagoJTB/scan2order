<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Makes product_id nullable and changes onDelete to 'set null'
     * to preserve order history even when products are deleted.
     */
    public function up()
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('order_items', function (Blueprint $table) {
            // Drop existing foreign key
            $table->dropForeign(['product_id']);
            
            // Make product_id nullable
            $table->unsignedBigInteger('product_id')->nullable()->change();
            
            // Re-add foreign key with set null on delete
            $table->foreign('product_id')
                ->references('id')->on('products')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('order_items', function (Blueprint $table) {
            // Drop the modified foreign key
            $table->dropForeign(['product_id']);
            
            // Revert product_id to not nullable
            $table->unsignedBigInteger('product_id')->nullable(false)->change();
            
            // Restore original foreign key with cascade
            $table->foreign('product_id')
                ->references('id')->on('products')
                ->onDelete('cascade');
        });
    }
};
