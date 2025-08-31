<?php

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
        // Create development/testing users
        User::updateOrCreate(
            ['email' => 'thomas@ekdahl.no'],
            [
                'name' => 'Thomas Ekdahl',
                'password' => Hash::make('ballball'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'tommyl@coretrek.no'],
            [
                'name' => 'Tommy L',
                'password' => Hash::make('ballball'),
            ]
        );
    }
}
