<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('placedorders', function (Blueprint $table) {
            $table->decimal('cart_total', 10, 2)->after('cart_id')->default(0.00);  // Add cart_total column
        });
    }

    public function down(): void
    {
        Schema::table('placedorders', function (Blueprint $table) {
            $table->dropColumn('cart_total');
        });
    }
};
