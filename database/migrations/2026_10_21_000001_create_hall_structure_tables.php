<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blocks', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 10)->unique();
            $table->timestamps();
        });

        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('block_id')->constrained()->restrictOnDelete();
            $table->string('room_no', 10);
            $table->unsignedTinyInteger('floor');
            $table->unsignedTinyInteger('capacity');
            $table->timestamps();

            $table->unique(['block_id', 'room_no']);
        });

        // Seats are never deleted (they carry allocation history); lowering capacity retires free seats instead.
        Schema::create('seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->restrictOnDelete();
            $table->char('label', 1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['room_id', 'label']);
        });

        Schema::create('seat_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seat_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_profile_id')->constrained()->restrictOnDelete();
            $table->date('allocated_at');
            $table->date('vacated_at')->nullable();
            $table->string('vacate_reason')->nullable();
            $table->foreignId('allocated_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('vacated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            // DB-level backup for BR-01 / BR-02: these columns equal the FK only while the
            // allocation is active (NULL once vacated), and NULLs never collide in a unique index.
            $table->unsignedBigInteger('active_seat_id')->nullable()
                ->storedAs('case when vacated_at is null then seat_id end')->unique();
            $table->unsignedBigInteger('active_student_profile_id')->nullable()
                ->storedAs('case when vacated_at is null then student_profile_id end')->unique();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seat_allocations');
        Schema::dropIfExists('seats');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('blocks');
    }
};
