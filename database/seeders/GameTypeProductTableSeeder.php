<?php

namespace Database\Seeders;

use App\Models\Admin\GameType;
use App\Models\Admin\GameTypeProduct;
use Illuminate\Database\Seeder;

class GameTypeProductTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'product_id' => 1,
                'game_type_id' => 1,
                'image' => 'pragmatic_play.jpeg',
                'rate' => '1.0000',
            ],
            [
                'product_id' => 2,
                'game_type_id' => 2,
                'image' => 'pragmatic_casino.png',
                'rate' => '1.0000',
            ],
            [
                'product_id' => 3,
                'game_type_id' => 3,
                'image' => 'sbo_sport.jpeg',
                'rate' => '1.0000',
            ],
            [
                'product_id' => 4,
                'game_type_id' => 4,
                'image' => 'joker.jpeg',
                'rate' => '1.0000',
            ],
            
        ];

        GameTypeProduct::insert($data);
    }
}
