<?php

use App\Models\LabAttendance;
use App\Models\Role;
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
        Schema::create('recent_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Role::class)->nullable();
            $table->foreignIdFor(LabAttendance::class)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recent_logs');
    }
};
