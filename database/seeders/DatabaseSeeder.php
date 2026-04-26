<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@souzou-office.local'],
            [
                'name'     => '管理者',
                'password' => bcrypt('password'),
                'role'     => 'admin',
                'is_active'=> true,
            ],
        );

        $this->call([
            TaskPlanningTemplateSeeder::class,
            DocumentTemplateSeeder::class,
        ]);
    }
}
