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
        Schema::create('student_attendances', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(); // Student's name
            $table->string('course')->nullable(); // Student's course
            $table->string('year')->nullable(); // Student's year level
            $table->string('block')->nullable(); // Student's block
            $table->string('student_number')->unique(); // Student's unique number
            $table->time('time_in')->nullable(); // Time in
            $table->time('time_out')->nullable(); // Time out
            $table->string('status')->nullable(); // Attendance status (present, absent, late)
            $table->timestamps(); // Created at and Updated at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_attendances');
    }
};
