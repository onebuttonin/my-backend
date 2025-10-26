<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_images', function (Blueprint $table) {
            $table->id();
            $table->string('image_path'); // image URL or file path
            $table->enum('screen_type', ['large', 'small']); // to separate large/small
            $table->integer('order')->default(0); // optional for ordering in slider
            $table->boolean('is_active')->default(true); // control visibility
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_images');
    }
};
