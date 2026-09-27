<?php

namespace Tests\Feature;

use App\Models\DetailPesanan;
use App\Models\Pesanan;
use Database\Seeders\MenuSeeder;
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

    public function test_customer_can_add_item_to_existing_order(): void
    {
        $menu = DB::table('menu')->insertGetId([
            'nama_menu' => 'Burger D',
            'harga' => 18000,
            'Kategori' => 'Makanan',
            'status_tersedia' => true,
        ]);

        $order = Pesanan::create([
            'nama' => 'Citra',
            'no_telepon' => '081122334466',
            'email' => 'citra@example.com',
            'total_harga' => 20000,
            'total_pesanan' => 1,
            'status_pembayaran' => 'Belum Lunas',
        ]);

        DetailPesanan::create([
            'id_pesanan' => $order->id_pesanan,
            'id_menu' => $menu,
            'jumlah' => 1,
            'harga_satuan' => 20000,
            'kustomisasi' => 'Tidak pedas',
        ]);

        $response = $this->postJson('/api/customer/orders/' . $order->id_pesanan . '/items', [
            'id_menu' => $menu,
            'qty' => 2,
            'harga_satuan' => 18000,
            'kustomisasi' => 'Pedas',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $this->assertDatabaseHas('detail_pesanan', [
            'id_pesanan' => $order->id_pesanan,
            'id_menu' => $menu,
            'jumlah' => 2,
            'kustomisasi' => 'Pedas',
        ]);

        $order->refresh();
        $this->assertEquals(3, $order->total_pesanan);
        $this->assertEquals(56000, (float) $order->total_harga);
    }

    public function test_customer_can_delete_specific_item_from_order(): void
    {
        $menuA = DB::table('menu')->insertGetId([
            'nama_menu' => 'Burger E',
            'harga' => 15000,
            'Kategori' => 'Makanan',
            'status_tersedia' => true,
        ]);

        $menuB = DB::table('menu')->insertGetId([
            'nama_menu' => 'Burger F',
            'harga' => 20000,
            'Kategori' => 'Makanan',
            'status_tersedia' => true,
        ]);

        $order = Pesanan::create([
            'nama' => 'Dinda',
            'no_telepon' => '081122334477',
            'email' => 'dinda@example.com',
            'total_harga' => 35000,
            'total_pesanan' => 2,
            'status_pembayaran' => 'Belum Lunas',
        ]);

        $detailA = DetailPesanan::create([
            'id_pesanan' => $order->id_pesanan,
            'id_menu' => $menuA,
            'jumlah' => 1,
            'harga_satuan' => 15000,
            'kustomisasi' => 'Tanpa timun',
        ]);

        $detailB = DetailPesanan::create([
            'id_pesanan' => $order->id_pesanan,
            'id_menu' => $menuB,
            'jumlah' => 1,
            'harga_satuan' => 20000,
            'kustomisasi' => 'Extra keju',
        ]);

        $response = $this->deleteJson('/api/customer/orders/' . $order->id_pesanan . '/items/' . $detailA->id_detail);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $this->assertDatabaseMissing('detail_pesanan', ['id_detail' => $detailA->id_detail]);

        $order->refresh();
        $this->assertEquals(1, $order->total_pesanan);
        $this->assertEquals(20000, (float) $order->total_harga);
    }

    public function test_customer_can_update_specific_item_in_order(): void
    {
        $menu = DB::table('menu')->insertGetId([
            'nama_menu' => 'Burger G',
            'harga' => 18000,
            'Kategori' => 'Makanan',
            'status_tersedia' => true,
        ]);

        $order = Pesanan::create([
            'nama' => 'Eka',
            'no_telepon' => '081122334488',
            'email' => 'eka@example.com',
            'total_harga' => 18000,
            'total_pesanan' => 1,
            'status_pembayaran' => 'Belum Lunas',
        ]);

        $detail = DetailPesanan::create([
            'id_pesanan' => $order->id_pesanan,
            'id_menu' => $menu,
            'jumlah' => 1,
            'harga_satuan' => 18000,
            'kustomisasi' => 'Awal',
        ]);

        $response = $this->patchJson('/api/customer/orders/' . $order->id_pesanan . '/items/' . $detail->id_detail, [
            'qty' => 3,
            'harga_satuan' => 20000,
            'kustomisasi' => 'Baru',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $this->assertDatabaseHas('detail_pesanan', [
            'id_detail' => $detail->id_detail,
            'jumlah' => 3,
            'harga_satuan' => 20000,
            'kustomisasi' => 'Baru',
        ]);

        $order->refresh();
        $this->assertEquals(3, $order->total_pesanan);
        $this->assertEquals(60000, (float) $order->total_harga);
    }

    public function test_customer_menu_endpoints_return_seeded_menu_data(): void
    {
        $this->artisan('db:seed', ['--class' => MenuSeeder::class])->assertOk();

        $response = $this->getJson('/api/customer/menus');
        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['id_menu', 'nama_menu', 'harga', 'Kategori', 'status_tersedia'],
            ],
        ]);

        $menu = DB::table('menu')->where('status_tersedia', true)->first();
        $this->assertNotNull($menu);

        $detailResponse = $this->getJson('/api/customer/menus/' . $menu->id_menu);
        $detailResponse->assertOk();
        $detailResponse->assertJsonPath('success', true);
        $detailResponse->assertJsonPath('data.id_menu', $menu->id_menu);
    }

    public function test_customer_can_post_specific_item_to_order(): void
    {
        $menu = DB::table('menu')->insertGetId([
            'nama_menu' => 'Burger H',
            'harga' => 22000,
            'Kategori' => 'Makanan',
            'status_tersedia' => true,
        ]);

        $order = Pesanan::create([
            'nama' => 'Fajar',
            'no_telepon' => '081122334499',
            'email' => 'fajar@example.com',
            'total_harga' => 0,
            'total_pesanan' => 0,
            'status_pembayaran' => 'Belum Lunas',
        ]);

        $detail = DetailPesanan::create([
            'id_pesanan' => $order->id_pesanan,
            'id_menu' => $menu,
            'jumlah' => 1,
            'harga_satuan' => 22000,
            'kustomisasi' => 'Awal',
        ]);

        $response = $this->postJson('/api/customer/orders/' . $order->id_pesanan . '/items/' . $detail->id_detail, [
            'id_menu' => $menu,
            'qty' => 3,
            'harga_satuan' => 24000,
            'kustomisasi' => 'Pedas',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('message', 'Item pesanan berhasil diperbarui.');
        $this->assertDatabaseHas('detail_pesanan', [
            'id_detail' => $detail->id_detail,
            'id_menu' => $menu,
            'jumlah' => 3,
            'harga_satuan' => 24000,
            'kustomisasi' => 'Pedas',
        ]);

        $order->refresh();
        $this->assertEquals(3, $order->total_pesanan);
        $this->assertEquals(72000, (float) $order->total_harga);
    }

    public function test_customer_can_create_order_then_checkout_with_order_id(): void
    {
        $menu = DB::table('menu')->insertGetId([
            'nama_menu' => 'Burger I',
            'harga' => 25000,
            'Kategori' => 'Makanan',
            'status_tersedia' => true,
        ]);

        $createResponse = $this->postJson('/api/customer/orders', [
            'nama' => 'Gilang',
            'phone' => '081234567890',
            'email' => 'gilang@example.com',
            'catatan' => 'Tanpa mayones',
            'items' => [
                [
                    'id_menu' => $menu,
                    'qty' => 2,
                    'harga_satuan' => 25000,
                    'kustomisasi' => 'Pedas',
                ],
            ],
        ]);

        $createResponse->assertStatus(201);
        $createResponse->assertJsonPath('success', true);
        $this->assertNotNull($createResponse->json('data.id_pesanan'));

        $orderId = $createResponse->json('data.id_pesanan');

        $checkoutResponse = $this->postJson('/api/customer/checkout', [
            'order_id' => $orderId,
            'nama' => 'Gilang',
            'phone' => '081234567890',
            'email' => 'gilang@example.com',
            'amount' => 50000,
            'paymentMethod' => 'kasir',
            'catatan' => 'Tanpa mayones',
        ]);

        $checkoutResponse->assertStatus(200);
        $checkoutResponse->assertJsonPath('success', true);
        $checkoutResponse->assertJsonPath('data.id_pesanan', $orderId);
        $this->assertDatabaseHas('pesanan', [
            'id_pesanan' => $orderId,
            'status_pembayaran' => 'Belum Lunas',
            'catatan' => 'Tanpa mayones',
        ]);
    }
}
