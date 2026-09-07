<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class TmpDevPasswordSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->where('email', 'admin@example.com')
            ->update(['password' => bcrypt('Zzz!98765'), 'root_admin' => true]);
    }
}
