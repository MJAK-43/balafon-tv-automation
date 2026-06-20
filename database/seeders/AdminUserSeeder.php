<?php

namespace Database\Seeders;

use App\Domains\Auth\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->updateOrCreate(
            ['email' => 'admin@balafon.local'],
            [
                'name' => 'Balafon Admin',
                'password' => 'password',
                'is_active' => true,
            ],
        );

        $role = Role::query()->where('code', 'admin')->first();

        if ($role !== null) {
            $user->roles()->syncWithoutDetaching([$role->id]);
        }
    }
}
