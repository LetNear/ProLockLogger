<?php

use App\Models\LabAttendance;
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
        Schema::create('lab_capacities', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(LabAttendance::class)->nullable();
            $table->string('max_cap');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_capacities');
    }
};
