<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clubs', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description', 1000);
            $table->string('logo_path')->nullable();
            $table->timestamps();
        });

        Schema::create('club_admins', function (Blueprint $table) {
            $table->foreignId('club_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['club_id', 'user_id']);
        });

        Schema::create('club_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_profile_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('pending')->index();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            // DB backup for BR-12: equals the student id only while pending or active, so a
            // student can have at most one open request per club but any number of closed ones.
            $table->unsignedBigInteger('open_student_profile_id')->nullable()
                ->storedAs("case when status in ('pending', 'active') then student_profile_id end");
            $table->unique(['club_id', 'open_student_profile_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_memberships');
        Schema::dropIfExists('club_admins');
        Schema::dropIfExists('clubs');
    }
};
