<?php

use App\Models\LabSchedule;
use App\Models\Seat;
use App\Models\User;
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
        Schema::create('lab_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->nullable();
            $table->foreignIdFor(Seat::class)->nullable();
            $table->foreignIdFor(LabSchedule::class)->nullable();
            $table->string('time_in')->nullable();
            $table->string('time_out')->nullable();
            $table->string('status')->nullable();
            $table->string('logdate')->nullable();
            $table->string('instructor')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_attendances');
    }
};
