<?php

namespace Tests\Feature;

use App\Models\DetailPesanan;
use App\Models\Pesanan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CustomerOrderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_update_order_and_recalculate_totals(): void
    {
        $menuA = DB::table('menu')->insertGetId([
            'nama_menu' => 'Burger A',
            'harga' => 20000,
            'Kategori' => 'Makanan',
            'status_tersedia' => true,
        ]);

        $menuB = DB::table('menu')->insertGetId([
            'nama_menu' => 'Burger B',
            'harga' => 25000,
            'Kategori' => 'Makanan',
            'status_tersedia' => true,
        ]);

        $order = Pesanan::create([
            'nama' => 'Andi',
            'no_telepon' => '081234567890',
            'email' => 'andi@example.com',
            'total_harga' => 30000,
            'total_pesanan' => 2,
            'status_pembayaran' => 'Belum Lunas',
            'catatan' => 'Awal',
        ]);

        DetailPesanan::create([
            'id_pesanan' => $order->id_pesanan,
            'id_menu' => $menuA,
            'jumlah' => 1,
            'harga_satuan' => 20000,
            'kustomisasi' => 'Awal',
        ]);

        $response = $this->patchJson('/api/customer/orders/' . $order->id_pesanan, [
            'nama' => 'Andi Updated',
            'phone' => '081234567891',
            'email' => 'andi.updated@example.com',
            'catatan' => 'Tanpa saos',
            'status_pembayaran' => 'Lunas',
            'items' => [
                [
                    'id_menu' => $menuB,
                    'qty' => 2,
                    'harga_satuan' => 25000,
                    'kustomisasi' => 'Pedas',
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $this->assertDatabaseHas('pesanan', [
            'id_pesanan' => $order->id_pesanan,
            'nama' => 'Andi Updated',
            'email' => 'andi.updated@example.com',
            'catatan' => 'Tanpa saos',
            'status_pembayaran' => 'Lunas',
            'total_pesanan' => 2,
            'total_harga' => 50000,
        ]);

        $this->assertDatabaseHas('detail_pesanan', [
            'id_pesanan' => $order->id_pesanan,
            'id_menu' => $menuB,
            'jumlah' => 2,
            'kustomisasi' => 'Pedas',
        ]);
    }

    public function test_customer_can_delete_order(): void
    {
        $menu = DB::table('menu')->insertGetId([
            'nama_menu' => 'Burger C',
            'harga' => 25000,
            'Kategori' => 'Makanan',
            'status_tersedia' => true,
        ]);

        $order = Pesanan::create([
            'nama' => 'Budi',
            'no_telepon' => '081122334455',
            'email' => 'budi@example.com',
            'total_harga' => 25000,
            'total_pesanan' => 1,
            'status_pembayaran' => 'Belum Lunas',
        ]);

        DetailPesanan::create([
            'id_pesanan' => $order->id_pesanan,
            'id_menu' => $menu,
            'jumlah' => 1,
            'harga_satuan' => 25000,
            'kustomisasi' => 'Tanpa bawang',
        ]);

        $response = $this->deleteJson('/api/customer/orders/' . $order->id_pesanan);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $this->assertDatabaseMissing('pesanan', ['id_pesanan' => $order->id_pesanan]);
        $this->assertDatabaseMissing('detail_pesanan', ['id_pesanan' => $order->id_pesanan]);
    }
}
