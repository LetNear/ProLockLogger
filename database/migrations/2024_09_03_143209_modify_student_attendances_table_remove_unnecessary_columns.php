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
        Schema::table('student_attendances', function (Blueprint $table) {
            $table->dropColumn('name');
            
            $table->dropColumn('year');
            $table->dropColumn('block');
            $table->dropColumn('student_number');
            $table->foreignId('user_information_id')->constrained('user_information');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_attendances', function (Blueprint $table) {
            $table->string('name')->nullable();
            
            $table->string('year')->nullable();
            $table->string('block')->nullable();
            $table->string('student_number')->nullable();
            $table->dropConstrainedForeignId('user_information_id');
        });
    }
};
