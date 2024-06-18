<?php

use App\Models\IdCard;
use App\Models\Role;
use App\Models\SeatPlanning;
use App\Models\YearAndProgram;
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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('user_id')->unique()->nullable();
            $table->foreignIdFor(IdCard::class);
            $table->foreignId(Role::class);
            $table->foreignId(SeatPlanning::class)->nullable();
            $table->foreignId(YearAndProgram::class)->nullable();
            //add id card, role id, seat id, yearlvl id as FK
            $table->string('name');
            $table->string('firstName')->nullable();
            $table->string('middleName')->nullable();
            $table->string('lastName')->nullable();
            $table->string('suffix')->nullable();
            $table->string('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->string('phone_no')->nullable();
            $table->string('email')->unique();
            $table->string('complete_address')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
