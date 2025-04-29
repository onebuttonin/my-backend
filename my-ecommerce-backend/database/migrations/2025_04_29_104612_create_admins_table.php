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
    Schema::create('admins', function (Blueprint $table) {
        $table->id(); // bigint unsigned auto_increment primary key
        $table->string('name');
        $table->string('email')->unique(); // unique key on email
        $table->string('password');
        $table->string('otp')->nullable();
        $table->timestamp('otp_expires_at')->nullable();
        $table->timestamps(); // created_at and updated_at
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
