<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

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

        // Check if admin user exists, if not, create the admin user
        if (!DB::table('users')->where('email', $adminEmail)->exists()) {
            DB::table('users')->insert([
                'name' => $adminName,
                'email' => $adminEmail,
                'password' => Hash::make($adminPassword),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create roles and permissions
            $role = Role::firstOrCreate(['name' => 'admin']);
            $permission = Permission::firstOrCreate(['name' => 'edit articles']);

            // Assign permission to role
            $role->givePermissionTo($permission);

            // Assign role to the newly created admin user
            $adminUser = DB::table('users')->where('email', $adminEmail)->first();
            DB::table('model_has_roles')->insert([
                'role_id' => $role->id,
                'model_type' => 'App\Models\User',
                'model_id' => $adminUser->id,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // You can optionally add code to reverse the roles and permissions creation if needed
    }
};
