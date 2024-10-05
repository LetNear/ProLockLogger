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
        Schema::table('seats', function (Blueprint $table) {
            $table->foreignIdFor(YearAndSemester::class)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seats', function (Blueprint $table) {
            $table->dropForeign(['year_and_semester_id']); // Drops the foreign key constraint
            $table->dropColumn('year_and_semester_id'); // Drops the column
        });
    }
};
