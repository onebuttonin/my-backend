<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Drop the unique constraint on cart_id
        Schema::table('carts', function (Blueprint $table) {
            $table->dropUnique('carts_cart_id_unique'); // Drop unique constraint
            $table->index('cart_id');  // Add regular index
        });
    }

    public function down()
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropIndex('carts_cart_id_index'); // Remove index
            $table->unique('cart_id');  // Reapply unique constraint
        });
    }
};
