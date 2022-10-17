<?php

namespace Database\Seeders;

use App\Models\Chat;
use App\Models\Deposit;
use App\Models\Issued;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $user = User::factory()->count(50)->create();

        $chat = Chat::factory()->count(10)
            ->hasAttached($user, ['role' => 'operator'])
            ->has(
                Shift::factory()->count(20)->has(
                    Deposit::factory()->count(25)
                )->has(
                    Issued::factory()->count(25)
                )
            )->create();
    }
}
