<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $menus = [
            [
                'nama_menu' => 'Classic Burger',
                'harga' => 25000,
                'Kategori' => 'Makanan',
                'status_tersedia' => true,
            ],
            [
                'nama_menu' => 'Cheese Burger',
                'harga' => 30000,
                'Kategori' => 'Makanan',
                'status_tersedia' => true,
            ],
            [
                'nama_menu' => 'Spicy Chicken Burger',
                'harga' => 28000,
                'Kategori' => 'Makanan',
                'status_tersedia' => true,
            ],
            [
                'nama_menu' => 'Kentang Goreng',
                'harga' => 12000,
                'Kategori' => 'Camilan',
                'status_tersedia' => true,
            ],
            [
                'nama_menu' => 'Milkshake',
                'harga' => 15000,
                'Kategori' => 'Minuman',
                'status_tersedia' => true,
            ],
        ];

        foreach ($menus as $menu) {
            DB::table('menu')->updateOrInsert(
                ['nama_menu' => $menu['nama_menu']],
                $menu
            );
        }
    }
}
