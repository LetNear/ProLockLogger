<?php

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
        Schema::table('course_user_information', function (Blueprint $table) {
            // Add the year_and_semester_id column to link to the year_and_semester table
            $table->foreignId('year_and_semester_id')->nullable()->after('schedule_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_user_information', function (Blueprint $table) {
            // Drop the year_and_semester_id column if the migration is rolled back
            $table->dropForeign('year_and_semester_id');
            $table->dropColumn('year_and_semester_id');
        });
    }
};
