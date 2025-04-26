<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();                // Coupon code
            $table->enum('type', ['fixed', 'percentage']);    // Discount type
            $table->decimal('value', 10, 2);                  // Discount value (fixed amount or percentage)
            $table->decimal('min_order_value', 10, 2)->nullable();  // Minimum order value to apply coupon
            $table->date('expires_at')->nullable();           // Expiration date
            $table->integer('usage_limit')->nullable();       // Max usage limit
            $table->integer('used_count')->default(0);        // Track how many times it has been used
            $table->boolean('is_active')->default(true);      // Enable or disable the coupon
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
