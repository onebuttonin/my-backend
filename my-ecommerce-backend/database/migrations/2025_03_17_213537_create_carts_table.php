<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id(); // Primary key (auto-increment)
            $table->bigInteger('cart_id')->unique(); // Unique cart identifier
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('size');
            $table->integer('quantity')->default(1);
            $table->string('status')->default('pending');
            $table->timestamps(); // created_at & updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('carts');
    }
};
