<?php
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Define the roles to be created
        $roles = [
            'Administrator' => 1, // Explicitly define role numbers
            'Faculty' => 2,
            'Student' => 3,
        ];

        // Create roles if they don't already exist and assign role numbers
        foreach ($roles as $roleName => $roleNumber) {
            Role::updateOrCreate(
                ['name' => $roleName],
                ['name' => $roleName]
            );
        }

        // Get the 'Administrator' role's ID
        $adminRole = Role::where('name', 'Administrator')->first();

        // Get user by email or create the user if it doesn't exist
        $user = User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'LabInCharge', // Updated name
                'password' => bcrypt('password'), // Default password
                'role_number' => 1, // Set role_number directly
            ]
        );

        // Assign the 'Administrator' role to the user
        if ($user && $adminRole) {
            $user->assignRole('Administrator');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optionally, you could remove the roles and the user if necessary
        Role::whereIn('name', ['Administrator', 'Faculty', 'Student'])->delete();

        $user = User::where('email', 'admin@admin.com')->first();
        if ($user) {
            $user->removeRole('Administrator');
            $user->delete(); // Or remove this line if you don't want to delete the user
        }
    }
};
