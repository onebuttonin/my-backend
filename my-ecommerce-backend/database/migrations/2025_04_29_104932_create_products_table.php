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
        Schema::create('products', function (Blueprint $table) {
            $table->id(); // bigint unsigned auto_increment primary key
            $table->tinyInteger('popularity')->default(1);
            $table->string('name');
            $table->decimal('price', 8, 2);
            $table->string('image')->nullable();
            $table->json('thumbnail_images')->nullable();
            $table->string('hover_image')->nullable();
            $table->text('description')->nullable();
            $table->json('availableSizes')->nullable();
            $table->json('availableColors')->nullable();
            $table->string('category')->nullable();
            $table->integer('stock')->default(0);
            $table->timestamps(); // created_at and updated_at
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
