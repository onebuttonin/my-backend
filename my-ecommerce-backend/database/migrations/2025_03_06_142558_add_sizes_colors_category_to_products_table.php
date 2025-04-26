<?php 

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('products', function (Blueprint $table) {
            $table->json('availableSizes')->nullable(); // Stores sizes as JSON
            $table->json('availableColors')->nullable(); // Stores colors in RGB as JSON
            $table->string('category')->nullable(); // Stores category as a string
        });
    }

    public function down() {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['availableSizes', 'availableColors', 'category']);
        });
    }
};
