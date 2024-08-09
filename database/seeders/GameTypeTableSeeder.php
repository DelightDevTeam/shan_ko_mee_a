<?php

namespace Database\Seeders;

use App\Models\Admin\GameType;
use Illuminate\Database\Seeder;

class GameTypeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'name' => 'Shan Ko Mee',
                'code' => '1',
                'order' => '1',
                'status' => 1,
                'img' => 'slots.png',
            ],
            [
                'name' => '21 Plays',
                'code' => '2',
                'order' => '2',
                'status' => 1,
                'img' => 'live_casino.png',
            ],
            [
                'name' => 'Poker',
                'code' => '3',
                'order' => '3',
                'status' => 1,
                'img' => 'sportbook.png',
            ],
            [
                'name' => 'ဘူးကြီး ဘူးလေး',
                'code' => '4',
                'order' => '4',
                'status' => 1,
                'img' => 'fishing.png',
            ],
            [
                'name' => 'Other',
                'code' => '6',
                'order' => '5',
                'status' => 1,
                'img' => 'other.png',
            ],
           
        ];

        GameType::insert($data);
    }
}
