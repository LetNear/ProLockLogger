<?php

use App\Models\Computer;
use App\Models\UserInformation;
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
        Schema::create('seats', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Computer::class)->nullable();
            $table->foreignIdFor(UserInformation::class, 'instructor_id')->nullable(); // Foreign key for instructor
            $table->string('instructor_name')->nullable(); // String to store the instructor's name
            $table->string('year_section');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seats');
    }
};
