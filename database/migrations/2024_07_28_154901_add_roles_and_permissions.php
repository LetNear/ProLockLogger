<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AddRolesAndPermissions extends Migration
{
    public function up()
    {
        // Create roles and permissions
        $role = Role::create(['name' => 'admin']);
        $permission = Permission::create(['name' => 'edit users']);
        $role->givePermissionTo($permission);
    }

    public function down()
    {
        // Remove roles and permissions if needed
        Role::where('name', 'admin')->delete();
        Permission::where('name', 'edit users')->delete();
    }
}
