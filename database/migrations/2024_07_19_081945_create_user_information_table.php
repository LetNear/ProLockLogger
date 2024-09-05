<?php

use App\Models\Block;
use App\Models\Course;
use App\Models\IdCard;
use App\Models\Nfc;
use App\Models\Seat;
use App\Models\User;
use App\Models\YearAndSemester;
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
        Schema::create('user_information', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)
                ->nullable()
                ->constrained()
                ->onDelete('cascade'); // Cascade delete for user
            $table->foreignIdFor(Nfc::class, 'id_card_id')->nullable();
            $table->foreignIdFor(Seat::class)->nullable();
            $table->string('user_number')->nullable();
            $table->string('year')->nullable();
            $table->foreignIdFor(Block::class)->nullable();
            $table->foreignIdFor(Course::class)->nullable(); // Add course_id column
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('suffix')->nullable();
            $table->string('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('complete_address')->nullable();
            $table->foreignIdFor(YearAndSemester::class, 'year_and_semester_id')->nullable(); // Add year_and_semester_id column
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_information');
    }
};
