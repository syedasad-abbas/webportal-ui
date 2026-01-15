<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        User::insert([
                'external_name' => 'Super Admin',
                'email' => 'superadmin@example.com',
                'internal_name' => 'superadmin',
                'password' => Hash::make('12345678'),
                 ]);

       
         $this->command->info('Seeded: Only Super Admin user created.');
    }
}
