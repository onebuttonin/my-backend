<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCostPriceToProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds a cost_price column to products to store the product's cost (snapshot source at order time).
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            // decimal(10,2) gives up to 99,999,999.99 which is fine for typical retail;
            // set default to 0.00 so older records are valid.
            $table->decimal('cost_price', 10, 2)->default(0)->after('price');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('cost_price');
        });
    }
}
