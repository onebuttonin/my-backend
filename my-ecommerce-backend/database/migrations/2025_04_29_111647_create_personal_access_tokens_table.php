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
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id(); // bigint unsigned auto_increment primary key
    
            $table->string('tokenable_type'); // for polymorphic relation (like App\Models\User, etc.)
            $table->unsignedBigInteger('tokenable_id');
            $table->string('name');
            $table->string('token', 64)->unique(); // 64-character token, unique
            $table->text('abilities')->nullable(); // permissions
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
    
            $table->timestamps(); // created_at and updated_at
    
            $table->index(['tokenable_type', 'tokenable_id']); // for faster lookup on polymorphic relation
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
