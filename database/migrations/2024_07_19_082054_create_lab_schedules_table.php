<?php

use App\Models\Block;
use App\Models\Course;
use App\Models\User;
use App\Models\UserInformation;
use App\Models\YearAndProgram;
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
        Schema::create('lab_schedules', function (Blueprint $table) {
            $table->id();
  
            $table->foreignIdFor(Course::class, 'course_id')->nullable();
            $table->foreignIdFor(User::class, 'instructor_id')->nullable();
            $table->foreignIdFor(Block::class)->nullable();
            $table->string('year')->nullable();
            $table->string('course_code')->nullable();
            $table->string('course_name')->nullable();
            $table->string('day_of_the_week')->nullable();
            $table->string('class_start')->nullable();
            $table->string('class_end')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_schedules');
    }
};
