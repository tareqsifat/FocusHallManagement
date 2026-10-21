<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // is_active is kept in step with users.status by HouseTutorService::setActive
        // (users.status is what actually blocks login).
        Schema::create('house_tutors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->restrictOnDelete();
            $table->string('designation');
            $table->string('department');
            $table->string('phone', 11);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('block_house_tutor', function (Blueprint $table) {
            $table->foreignId('block_id')->constrained()->cascadeOnDelete();
            $table->foreignId('house_tutor_id')->constrained()->cascadeOnDelete();
            $table->primary(['block_id', 'house_tutor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('block_house_tutor');
        Schema::dropIfExists('house_tutors');
    }
};
