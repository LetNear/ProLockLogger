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
        Schema::create('seat_plannings', function (Blueprint $table) {
            $table->id();
            $table->string('seat_id')->unique();
            $table->string('computer_no');
            $table->string('instructor');
            $table->string('year_section');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seat_plannings');
    }
};
