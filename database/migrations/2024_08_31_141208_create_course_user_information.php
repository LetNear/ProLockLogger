<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('course_user_information', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_information_id')->nullable(); // Links to user_information
            $table->foreignId('course_id')->nullable(); // Links to courses
            $table->foreignId('schedule_id')->nullable(); // Links to schedules, add this line
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_user_information');
    }
};
