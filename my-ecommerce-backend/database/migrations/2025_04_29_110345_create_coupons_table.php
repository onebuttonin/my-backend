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
    Schema::create('coupons', function (Blueprint $table) {
        $table->id(); // bigint unsigned auto_increment primary key

        $table->foreignId('cart_id')->nullable()->constrained('carts')->onDelete('set null');
        $table->string('code')->unique(); // unique coupon code
        $table->enum('type', ['fixed', 'percentage']);
        $table->decimal('value', 10, 2);
        $table->decimal('min_order_value', 10, 2)->nullable();
        $table->date('expires_at')->nullable();
        $table->integer('usage_limit')->nullable();
        $table->integer('used_count')->default(0); // default value as 0
        $table->boolean('is_active')->default(true); // default active coupon

        $table->timestamps(); // created_at and updated_at
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
