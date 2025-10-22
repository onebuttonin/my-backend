<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up()
{
    Schema::table('product_ratings', function (Blueprint $table) {
        $table->string('review_image')->nullable()->after('review');
    });
}

public function down()
{
    Schema::table('product_ratings', function (Blueprint $table) {
        $table->dropColumn('review_image');
    });
}

};
