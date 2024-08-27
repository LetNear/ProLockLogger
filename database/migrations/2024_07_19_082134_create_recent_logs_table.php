<?php

use App\Models\Block;
use App\Models\LabAttendance;
use App\Models\Nfc;
use App\Models\Role;
use App\Models\User;
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
        Schema::create('recent_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Block::class, 'block_id')->nullable();
            $table->foreignIdFor(Nfc::class, 'id_card_id')->nullable();
            $table->foreignIdFor(Role::class, 'role_id')->nullable();
            $table->string('user_number')->nullable();
            $table->string('year')->nullable();
            $table->string('time_in')->nullable();
            $table->string('time_out')->nullable();
            $table->string('rfid_number')->nullable();
      
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
