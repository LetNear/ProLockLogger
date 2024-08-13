<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Define the roles to be created with corresponding role numbers
        $roles = [
            1 => 'Administrator',
            2 => 'Faculty',
            3 => 'Student',
        ];

        // Create roles if they don't already exist and log the process
        foreach ($roles as $roleNumber => $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            if ($role->wasRecentlyCreated) {
                Log::info("Role created: {$roleName}");
            } else {
                Log::info("Role already exists: {$roleName}");
            }
        }

        // Create or update the 'admin@admin.com' user
        $user = User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'LabInCharge',
                'password' => bcrypt('password'), // Use a strong password in production
                'role_number' => 1, // Assign the role number directly
            ]
        );

        // Assign the 'Administrator' role to the user and log the assignment
        if ($user->assignRole('Administrator')) {
            Log::info("Administrator role assigned to user: {$user->email}");
        } else {
            Log::error("Failed to assign Administrator role to user: {$user->email}");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the roles and log the process
        Role::whereIn('name', ['Administrator', 'Faculty', 'Student'])->delete();
        Log::info("Roles removed: Administrator, Faculty, Student");

        // Optionally, remove the user
        $user = User::where('email', 'admin@admin.com')->first();
        if ($user) {
            $user->removeRole('Administrator');
            $user->delete();
            Log::info("User deleted: {$user->email}");
        }
    }
};
