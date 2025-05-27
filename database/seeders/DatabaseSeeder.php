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
        // AIユーザーシーダーを呼び出す
        $this->call(AIUserSeeder::class);

        // $this->call(DemoDebateSeeder::class);

        $this->call(DeathPenaltyDebateSeeder::class);

        $this->call(SETIDebateSeeder::class);

        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
