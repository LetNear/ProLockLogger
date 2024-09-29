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
        Schema::table('nfcs', function (Blueprint $table) {
            // Drop unique constraint on rfid_number
            $table->dropUnique(['rfid_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nfcs', function (Blueprint $table) {
            // Add unique constraint back on rfid_number
            $table->unique('rfid_number');
        });
    }
};
