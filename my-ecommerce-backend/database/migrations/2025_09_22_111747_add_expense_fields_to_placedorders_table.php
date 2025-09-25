<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExpenseFieldsToPlacedordersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds delivery_charges, packaging_cost, payment_gateway_fee, is_prepaid,
     * items_cost_sum, total_expense, net_profit and items_snapshot JSON.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('placedorders', function (Blueprint $table) {
            $table->decimal('delivery_charges', 10, 2)->default(0)->after('cart_total');
            $table->decimal('packaging_cost', 10, 2)->default(0)->after('delivery_charges');
            $table->decimal('payment_gateway_fee', 10, 2)->default(0)->after('packaging_cost');
            $table->boolean('is_prepaid')->default(false)->after('payment_method');

            // computed sums / snapshots
            $table->decimal('items_cost_sum', 12, 2)->nullable()->after('payment_gateway_fee');
            $table->decimal('total_expense', 12, 2)->nullable()->after('items_cost_sum');
            $table->decimal('net_profit', 12, 2)->nullable()->after('total_expense');

            // JSON snapshot of items (product id, qty, selling price, cost_price)
            $table->json('items_snapshot')->nullable()->after('net_profit');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('placedorders', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_charges',
                'packaging_cost',
                'payment_gateway_fee',
                'is_prepaid',
                'items_cost_sum',
                'total_expense',
                'net_profit',
                'items_snapshot',
            ]);
        });
    }
}
