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
                'is_admin' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'tommyl@coretrek.no'],
            [
                'name' => 'Tommy L',
                'password' => Hash::make('ballball'),
                'is_admin' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'linus@ekdahl.no'],
            [
                'name' => 'Linus Ekdahl',
                'password' => Hash::make('ballball'),
                'is_admin' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'lilje@ekdahl.no'],
            [
                'name' => 'Lilje Ekdahl',
                'password' => Hash::make('ballball'),
                'is_admin' => true,
            ]
        );
    }
}
