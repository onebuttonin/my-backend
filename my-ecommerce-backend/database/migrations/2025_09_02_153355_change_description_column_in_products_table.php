<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            // Change description column to JSON
            $table->json('description')->change();
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            // Rollback to string (if needed)
            $table->text('description')->change();
        });
    }
};
