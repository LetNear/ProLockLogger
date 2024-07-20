<?php

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
            $table->foreignIdFor(YearAndProgram::class)->nullable();
            $table->string('subject_code');
            $table->string('subject_name');
            $table->string('instructor');
            $table->string('day_of_the_week');
            $table->string('class_start');
            $table->string('class_end');
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
