<?php

namespace Database\Seeders;

use App\Domains\Auth\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Administrator', 'code' => 'admin'],
            ['name' => 'Planner', 'code' => 'planner'],
            ['name' => 'Operator', 'code' => 'operator'],
            ['name' => 'Viewer', 'code' => 'viewer'],
        ] as $role) {
            Role::query()->updateOrCreate(['code' => $role['code']], $role);
        }
    }
}
