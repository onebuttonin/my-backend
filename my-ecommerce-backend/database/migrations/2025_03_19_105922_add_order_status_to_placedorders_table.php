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
        Schema::table('placedorders', function (Blueprint $table) {
            $table->string('order_status')->default('pending')->after('payment_method'); 
            // Added default status as 'pending'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('placedorders', function (Blueprint $table) {
            $table->dropColumn('order_status');
        });
    }
};
