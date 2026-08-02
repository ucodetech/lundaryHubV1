<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions
        $permissions = [
            'manage-shops',
            'manage-riders',
            'manage-users',
            'view-analytics',
            'manage-commissions',
            'manage-disputes',
            'manage-shop',
            'manage-services',
            'manage-pricing',
            'manage-orders',
            'manage-staff',
            'view-earnings',
            'withdraw-funds',
            'accept-pickups',
            'accept-deliveries',
            'update-delivery-status',
            'place-orders',
            'track-orders',
            'write-reviews',
            'manage-favorites',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Assign Permissions to Roles
        $superAdminRole = Role::findOrCreate(UserRole::SUPER_ADMIN->value, 'web');
        $superAdminRole->givePermissionTo(Permission::all());

        $shopOwnerRole = Role::findOrCreate(UserRole::SHOP_OWNER->value, 'web');
        $shopOwnerRole->givePermissionTo([
            'manage-shop',
            'manage-services',
            'manage-pricing',
            'manage-orders',
            'manage-staff',
            'view-earnings',
            'withdraw-funds',
        ]);

        $riderRole = Role::findOrCreate(UserRole::RIDER->value, 'web');
        $riderRole->givePermissionTo([
            'accept-pickups',
            'accept-deliveries',
            'update-delivery-status',
            'view-earnings',
        ]);

        $customerRole = Role::findOrCreate(UserRole::CUSTOMER->value, 'web');
        $customerRole->givePermissionTo([
            'place-orders',
            'track-orders',
            'write-reviews',
            'manage-favorites',
        ]);
    }
}
