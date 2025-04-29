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
    Schema::create('cache', function (Blueprint $table) {
        $table->string('key')->primary(); // Primary key
        $table->mediumText('value'); // Stored cache data
        $table->integer('expiration'); // Expiration time as integer (usually a timestamp)
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cache');
    }
};
