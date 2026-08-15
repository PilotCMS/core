<?php

namespace Pilot\Core\Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            BlockTypeSeeder::class,
        ]);
    }
}
