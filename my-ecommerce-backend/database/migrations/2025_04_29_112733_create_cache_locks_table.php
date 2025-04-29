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
    Schema::create('cache_locks', function (Blueprint $table) {
        $table->string('key')->primary();    // Lock key (Primary Key)
        $table->string('owner');             // Owner identifier (usually a UUID or unique string)
        $table->integer('expiration');       // Expiration timestamp
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cache_locks');
    }
};
