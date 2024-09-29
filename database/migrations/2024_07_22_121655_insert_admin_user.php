<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $adminName = config('app.admin.name');
        $adminEmail = config('app.admin.email');
        $adminPassword = config('app.admin.password');
        
        // Define fingerprint IDs as JSON objects
        $adminFinger = json_encode([
            ['fingerprint_id' => '1'], 
            ['fingerprint_id' => '2']
        ]); 

        // Check if the admin user already exists based on the email
        if (!DB::table('users')->where('email', $adminEmail)->exists()) {
            DB::table('users')->insert([
                'name' => $adminName,
                'email' => $adminEmail,
                'password' => Hash::make($adminPassword),
                'fingerprint_id' => $adminFinger, // Insert as a JSON object array
                'created_at' => now(),
                'updated_at' => now(),
                'is_protected' => true, // Flag for protected admin
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Do not delete the admin user in the rollback
    }
};
