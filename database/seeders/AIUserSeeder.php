<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AIUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['id' => 9],
            [
                'name' => 'AI Assistant',
                'email' => 'ai@debatematch.app',
                'password' => Hash::make(str()->random(20)),
                'email_verified_at' => now(),
                'is_admin' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
