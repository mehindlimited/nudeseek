<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $this->call(CountrySeeder::class);
        $this->call(TargetSeeder::class);
        $this->call(CategorySeeder::class);


        User::create([
            'username' => 'MrSeek',
            'email' => 'info@nudeseek.com',
            'role' => 'admin',
            'password' => bcrypt('Novarano1234&8'), // Set a secure password
        ]);
    }
}
