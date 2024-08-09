<?php

use App\Models\Block;
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
  
            $table->string('subject_code')->nullable();
            $table->string('subject_name')->nullable();
            $table->foreignIdFor(UserInformation::class, 'instructor_id')->nullable(); // Foreign key for instructor
            $table->string('instructor_name')->nullable(); // String to store the instructor's name
            $table->foreignIdFor(Block::class)->nullable();
            $table->string('year')->nullable();
            $table->string('day_of_the_week')->nullable();
            $table->string('class_start')->nullable();
            $table->string('class_end')->nullable();
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
