<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates admin and assistant users for development/demo.
     * Password is hashed via User model cast.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin',
                'email' => 'admin@intent1.local',
                'role' => 'admin',
                'password' => 'password',
            ],
            [
                'name' => 'Assistant',
                'email' => 'assistant@intent1.local',
                'role' => 'assistant',
                'password' => 'password',
            ],
        ];

        foreach ($users as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                $data
            );
        }
    }
}
