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
        Schema::create('placedorders', function (Blueprint $table) {
            $table->id(); // bigint unsigned auto_increment primary key
    
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // Optional for guest users
            $table->string('name');
            $table->string('street1');
            $table->string('street2')->nullable();
            $table->string('city');
            $table->string('state');
            $table->string('pincode');
            $table->string('mobile');
            $table->string('payment_method');
            $table->string('order_status')->default('pending'); // Default status as 'pending'
            $table->foreignId('cart_id')->constrained('carts')->onDelete('cascade'); // links to the cart
            $table->decimal('cart_total', 10, 2)->default(0.00); // Default cart total as 0.00
    
            $table->timestamps(); // created_at and updated_at
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('placedorders');
    }
};
