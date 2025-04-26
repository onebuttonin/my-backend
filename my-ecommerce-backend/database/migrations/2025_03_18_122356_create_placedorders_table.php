<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('placedorders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('street1');
            $table->string('street2')->nullable();
            $table->string('city');
            $table->string('state');
            $table->string('pincode');
            $table->string('mobile');
            $table->string('payment_method');
            $table->bigInteger('cart_id');  // No foreign key constraint here
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('placedorders');
    }
};
