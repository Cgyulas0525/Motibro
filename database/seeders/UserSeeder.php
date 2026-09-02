<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Cseszneki Gyula',
                'email' => 'cseszneki.gyula@gmail.com',
                'password' => 'A1234567',
            ],
            [
                'name' => 'Cseszneki-Papp Ágnes',
                'email' => 'csesneki.agi@gmail.com',
                'password' => 'B1234567',
            ],
        ];

        foreach ($users as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => $data['password'],
                    'email_verified_at' => now(),
                ],
            );
        }
    }
}
