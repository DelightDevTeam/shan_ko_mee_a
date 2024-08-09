<?php

namespace Database\Seeders;

use App\Models\Admin\Product;
use Illuminate\Database\Seeder;

class ProductTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'code' => '1001',
                'name' => 'Shan',
                'short_name' => 'Shan',
                'order' => 1,
            ],
            [
                'code' => '1002',
                'name' => '21 Play Card',
                'short_name' => '21',
                'order' => 2,
                'game_list_status' => '1',
            ],
            [
                'code' => '1003',
                'name' => 'Poker',
                'short_name' => 'Poker',
                'order' => 3,
            ],
            [
                'code' => '1004',
                'name' => 'ဘူးကြီး ဘူးလေး',
                'short_name' => 'ဘူးကြီး ဘူးလေး',
                'order' => 13,
                'game_list_status' => '1',
            ],
           
        ];

        //Product::insert($data);
        foreach ($data as $obj) {
            Product::create($obj);
        }

    }
}
