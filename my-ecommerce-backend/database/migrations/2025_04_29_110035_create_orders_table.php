<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('orders', function (Blueprint $table) {
        $table->id(); // bigint unsigned auto_increment primary key

        $table->string('order_number')->unique(); // unique order number
        $table->json('items'); // JSON object with cart/order details
        $table->decimal('total_price', 10, 2);

        $table->enum('status', ['pending', 'processing', 'shipped', 'delivered', 'cancelled'])->default('pending');
        $table->string('payment_method')->nullable(); // e.g., COD, Razorpay, etc.
        $table->string('payment_status')->default('pending'); // pending, paid, failed, etc.

        $table->text('shipping_address')->nullable();
        $table->text('billing_address')->nullable();

        $table->timestamps(); // created_at and updated_at
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
