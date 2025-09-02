<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TargetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $targets = [
            ['name' => 'Male'],
            ['name' => 'Female'],
        ];
        DB::table('targets')->insert($targets);
    }
}
