<?php

namespace App\Http\Controllers;

use App\Models\DetailPesanan;
use App\Models\Menu;
use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CustomerApiController extends Controller
{
    /**
     * GET /api/customer/menus
     * Menampilkan menu yang tersedia untuk pelanggan.
     */
    public function index()
    {
        $menus = Menu::where('status_tersedia', true)
            ->get()
            ->map(function ($menu) {
                $menu->foto_url = url('/api/menu/' . $menu->id_menu . '/foto');
                $menu->makeHidden('foto');
                return $menu;
            });

        return response()->json([
            'success' => true,
            'data' => $menus,
        ]);
    }

    /**
     * GET /api/customer/menus/{id}
     * Detail satu menu untuk pelanggan.
     */
    public function show($id)
    {
        $menu = Menu::with('bahan')->findOrFail($id);
        $menu->foto_url = url('/api/menu/' . $menu->id_menu . '/foto');
        $menu->makeHidden('foto');

        return response()->json([
            'success' => true,
            'data' => $menu,
        ]);
    }

    /**
     * POST /api/customer/orders
     * Membuat order terlebih dahulu sebelum checkout.
     */
    public function createOrder(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'phone' => ['required', 'regex:/^(?:\+62|08)[0-9]{8,13}$/'],
            'email' => 'required|email',
            'items' => 'required|array|min:1',
        ], [
            'nama.required' => 'Nama pelanggan wajib diisi.',
            'phone.required' => 'Nomor telepon wajib diisi.',
            'phone.regex' => 'Format nomor telepon tidak valid.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'items.required' => 'Item pesanan tidak boleh kosong.',
            'items.min' => 'Item pesanan tidak boleh kosong.',
        ]);

        $items = $request->input('items', []);
        $nama = $request->input('nama');
        $phone = $request->input('phone');
        $email = $request->input('email');
        $catatan = $request->input('catatan');

        $totalPesanan = 0;
        $totalHarga = 0;

        foreach ($items as $item) {
            $qty = (int) ($item['qty'] ?? 0);
            $hargaSatuan = (float) ($item['harga_satuan'] ?? 0);
            $totalPesanan += $qty;
            $totalHarga += $qty * $hargaSatuan;
        }

        $pesanan = DB::transaction(function () use ($nama, $phone, $email, $catatan, $items, $totalPesanan, $totalHarga) {
            $newPesanan = Pesanan::create([
                'nama' => $nama,
                'no_telepon' => $phone,
                'email' => $email,
                'total_harga' => $totalHarga,
                'total_pesanan' => $totalPesanan,
                'status_pembayaran' => 'Belum Lunas',
                'catatan' => $catatan,
            ]);

            foreach ($items as $item) {
                DetailPesanan::create([
                    'id_pesanan' => $newPesanan->id_pesanan,
                    'id_menu' => $item['id_menu'] ?? $item['id'],
                    'jumlah' => (int) ($item['qty'] ?? 0),
                    'harga_satuan' => (float) ($item['harga_satuan'] ?? 0),
                    'kustomisasi' => $item['kustomisasi'] ?? $item['notes'] ?? $item['note'] ?? null,
                ]);
            }

            return $newPesanan;
        });

        return response()->json([
            'success' => true,
            'message' => 'Order berhasil dibuat. Silakan lanjut ke proses checkout.',
            'data' => [
                'id_pesanan' => $pesanan->id_pesanan,
                'total_pesanan' => $pesanan->total_pesanan,
                'total_harga' => $pesanan->total_harga,
                'status_pembayaran' => $pesanan->status_pembayaran,
            ],
        ], 201);
    }

    /**
     * PATCH /api/customer/orders/{id}
     * Update data order dan item-itemnya.
     */
    public function updateOrder(Request $request, $id)
    {
        $pesanan = Pesanan::with('detailPesanan')->findOrFail($id);

        $request->validate([
            'nama' => 'sometimes|string|max:255',
            'phone' => ['sometimes', 'regex:/^(?:\+62|08)[0-9]{8,13}$/'],
            'email' => 'sometimes|email',
            'catatan' => 'sometimes|nullable|string',
            'status_pembayaran' => 'sometimes|string',
            'items' => 'sometimes|array',
        ]);

        if ($request->has('nama')) {
            $pesanan->nama = $request->input('nama');
        }

        if ($request->has('phone')) {
            $pesanan->no_telepon = $request->input('phone');
        }

        if ($request->has('email')) {
            $pesanan->email = $request->input('email');
        }

        if ($request->has('catatan')) {
            $pesanan->catatan = $request->input('catatan');
        }

        if ($request->has('status_pembayaran')) {
            $pesanan->status_pembayaran = $request->input('status_pembayaran');
        }

        if ($request->has('items')) {
            $items = $request->input('items', []);
            $pesanan->detailPesanan()->delete();

            $totalPesanan = 0;
            $totalHarga = 0;

            foreach ($items as $item) {
                $qty = (int) ($item['qty'] ?? 0);
                $hargaSatuan = (float) ($item['harga_satuan'] ?? 0);
                $totalPesanan += $qty;
                $totalHarga += $qty * $hargaSatuan;

                $pesanan->detailPesanan()->create([
                    'id_menu' => $item['id_menu'] ?? $item['id'],
                    'jumlah' => $qty,
                    'harga_satuan' => $hargaSatuan,
                    'kustomisasi' => $item['kustomisasi'] ?? $item['notes'] ?? $item['note'] ?? null,
                ]);
            }

            $pesanan->total_pesanan = $totalPesanan;
            $pesanan->total_harga = $totalHarga;
        }

        $pesanan->save();

        return response()->json([
            'success' => true,
            'message' => 'Order berhasil diperbarui.',
            'data' => [
                'id_pesanan' => $pesanan->id_pesanan,
                'total_pesanan' => $pesanan->total_pesanan,
                'total_harga' => $pesanan->total_harga,
            ],
        ]);
    }

    /**
     * DELETE /api/customer/orders/{id}
     * Hapus order beserta detailnya.
     */
    public function deleteOrder($id)
    {
        $pesanan = Pesanan::with('detailPesanan')->findOrFail($id);
        $pesanan->detailPesanan()->delete();
        $pesanan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Order berhasil dihapus.',
        ]);
    }

    /**
     * POST /api/customer/checkout
     * Membuat pesanan pelanggan dari cart atau order yang sudah dibuat.
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'order_id' => 'nullable|integer|min:1',
            'nama' => 'required_without:order_id|string|max:255',
            'phone' => ['required_without:order_id', 'regex:/^(?:\+62|08)[0-9]{8,13}$/'],
            'email' => 'required_without:order_id|email',
            'cart' => 'nullable|array|min:1',
            'amount' => 'nullable|numeric|min:1',
        ], [
            'nama.required_without' => 'Nama pelanggan wajib diisi.',
            'phone.required_without' => 'Nomor telepon wajib diisi.',
            'phone.regex' => 'Format nomor telepon tidak valid.',
            'email.required_without' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'cart.min' => 'Keranjang pesanan tidak boleh kosong.',
            'amount.min' => 'Total pembayaran wajib diisi.',
        ]);

        $orderId = $request->input('order_id');
        $paymentMethod = $request->input('paymentMethod', 'online');

        $pesanan = null;
        if ($orderId) {
            $pesanan = Pesanan::with('detailPesanan')->find($orderId);
            if (! $pesanan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order tidak ditemukan.',
                ], 404);
            }
        }

        $nama = $request->input('nama', $pesanan?->nama);
        $phone = $request->input('phone', $pesanan?->no_telepon);
        $email = $request->input('email', $pesanan?->email);
        $catatan = $request->input('catatan', $pesanan?->catatan);
        $cart = $request->input('cart', $pesanan?->detailPesanan->map(function ($detail) {
            return [
                'id' => $detail->id_menu,
                'qty' => (int) $detail->jumlah,
                'price' => (float) $detail->harga_satuan * (int) $detail->jumlah,
                'notes' => $detail->kustomisasi,
            ];
        })->values()->all() ?? []);

        if (empty($cart) && ! $pesanan) {
            return response()->json([
                'success' => false,
                'message' => 'Keranjang pesanan tidak boleh kosong.',
            ], 400);
        }

        $amount = (float) ($request->input('amount') ?? $pesanan?->total_harga ?? 0);

        if (! $pesanan) {
            $merchantCode = env('DUITKU_MERCHANT_CODE');
            $apiKey = env('DUITKU_API_KEY');

            if (! $merchantCode || ! $apiKey) {
                return response()->json([
                    'success' => false,
                    'message' => 'Konfigurasi Duitku belum diset. Isi DUITKU_MERCHANT_CODE dan DUITKU_API_KEY di file .env.',
                ], 500);
            }

            $totalPesanan = 0;
            foreach ($cart as $item) {
                $totalPesanan += (int) ($item['qty'] ?? 0);
            }

            $reference = 'SB-' . time() . '-' . Str::upper(Str::random(6));

            $pesanan = DB::transaction(function () use ($nama, $phone, $email, $amount, $catatan, $cart, $reference, $totalPesanan) {
                $newPesanan = Pesanan::create([
                    'nama' => $nama,
                    'no_telepon' => $phone,
                    'email' => $email,
                    'total_harga' => $amount,
                    'total_pesanan' => $totalPesanan,
                    'status_pembayaran' => 'Belum Lunas',
                    'payment_reference' => $reference,
                    'catatan' => $catatan,
                ]);

                foreach ($cart as $item) {
                    DetailPesanan::create([
                        'id_pesanan' => $newPesanan->id_pesanan,
                        'id_menu' => $item['id'],
                        'jumlah' => (int) ($item['qty'] ?? 0),
                        'harga_satuan' => (float) ($item['price'] ?? 0),
                        'kustomisasi' => $item['notes'] ?? $item['note'] ?? null,
                    ]);
                }

                return $newPesanan;
            });
        } else {
            $pesanan->nama = $nama;
            $pesanan->no_telepon = $phone;
            $pesanan->email = $email;
            $pesanan->catatan = $catatan;
            $pesanan->total_harga = $amount ?: $pesanan->total_harga;
            $pesanan->save();
        }

        if (strtolower((string) $paymentMethod) === 'kasir') {
            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil dibuat. Silakan bayar di kasir.',
                'data' => [
                    'id_pesanan' => $pesanan->id_pesanan,
                    'status_pembayaran' => $pesanan->status_pembayaran,
                    'payment_method' => 'kasir',
                    'order_id' => $pesanan->id_pesanan,
                ],
            ], 200);
        }

        $merchantCode = env('DUITKU_MERCHANT_CODE');
        $apiKey = env('DUITKU_API_KEY');

        if (! $merchantCode || ! $apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'Konfigurasi Duitku belum diset. Isi DUITKU_MERCHANT_CODE dan DUITKU_API_KEY di file .env.',
            ], 500);
        }

        $reference = $pesanan->payment_reference ?: 'SB-' . time() . '-' . Str::upper(Str::random(6));
        $merchantOrderId = $reference;
        $signature = md5($merchantCode . $merchantOrderId . $amount . $apiKey);
        $callbackUrl = url('/api/duitku/callback');
        $returnUrl = url('/keranjang');

        $duitkuResponse = Http::asForm()->post('https://sandbox.duitku.com/webapi/api/merchant/v2/inquiry', [
            'merchantcode' => $merchantCode,
            'merchantOrderId' => $merchantOrderId,
            'paymentAmount' => (string) $amount,
            'paymentMethod' => 'SP',
            'productDetails' => 'Pembayaran Pesanan Senyawa Burger',
            'email' => $email,
            'customerVaName' => $nama,
            'callbackUrl' => $callbackUrl,
            'returnUrl' => $returnUrl,
            'signature' => $signature,
            'expiryPeriod' => 15,
        ]);

        if (! $duitkuResponse->successful() || empty($duitkuResponse['qrString'])) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat link pembayaran Duitku.',
                'details' => $duitkuResponse->json(),
            ], 400);
        }

        $pesanan->payment_reference = $reference;
        $pesanan->save();

        return response()->json([
            'success' => true,
            'message' => 'Pesanan berhasil dibuat. Silakan lanjut ke pembayaran.',
            'data' => [
                'id_pesanan' => $pesanan->id_pesanan,
                'order_id' => $pesanan->id_pesanan,
                'payment_reference' => $reference,
                'qrString' => $duitkuResponse['qrString'],
                'payment_method' => 'online',
                'status_pembayaran' => $pesanan->status_pembayaran,
                'total_harga' => $pesanan->total_harga,
            ],
        ], 201);
    }

    /**
     * POST /api/customer/orders/{id}/items
     * Tambah satu item ke pesanan yang sudah ada.
     */
    public function addOrderItem(Request $request, $id)
    {
        $pesanan = Pesanan::findOrFail($id);

        $request->validate([
            'id_menu' => 'required|integer|min:1',
            'qty' => 'required|integer|min:1',
            'harga_satuan' => 'nullable|numeric|min:0',
            'kustomisasi' => 'nullable|string',
        ], [
            'id_menu.required' => 'ID menu wajib diisi.',
            'qty.required' => 'Jumlah item wajib diisi.',
            'qty.integer' => 'Jumlah item harus berupa angka.',
            'harga_satuan.numeric' => 'Harga satuan harus berupa angka.',
        ]);

        $detail = $pesanan->detailPesanan()->create([
            'id_menu' => (int) $request->id_menu,
            'jumlah' => (int) $request->qty,
            'harga_satuan' => (float) ($request->harga_satuan ?? 0),
            'kustomisasi' => $request->kustomisasi,
        ]);

        $pesanan->total_pesanan = $pesanan->detailPesanan()->sum('jumlah');
        $pesanan->total_harga = (float) $pesanan->detailPesanan()->sum(DB::raw('jumlah * harga_satuan'));
        $pesanan->save();

        return response()->json([
            'success' => true,
            'message' => 'Item berhasil ditambahkan ke pesanan.',
            'data' => [
                'id_pesanan' => $pesanan->id_pesanan,
                'item' => $detail,
                'total_pesanan' => $pesanan->total_pesanan,
                'total_harga' => $pesanan->total_harga,
            ],
        ], 201);
    }

    /**
     * POST /api/customer/orders/{id}/items/{detail_id}
     * Tambah atau update item tertentu dalam pesanan berdasarkan id detail.
     */
    public function upsertOrderItem(Request $request, $id, $detail_id)
    {
        $pesanan = Pesanan::findOrFail($id);

        $request->validate([
            'id_menu' => 'required|integer|min:1',
            'qty' => 'required|integer|min:1',
            'harga_satuan' => 'nullable|numeric|min:0',
            'kustomisasi' => 'nullable|string',
        ], [
            'id_menu.required' => 'ID menu wajib diisi.',
            'qty.required' => 'Jumlah item wajib diisi.',
            'qty.integer' => 'Jumlah item harus berupa angka.',
            'harga_satuan.numeric' => 'Harga satuan harus berupa angka.',
        ]);

        $detail = $pesanan->detailPesanan()->where('id_detail', $detail_id)->first();

        if ($detail) {
            $detail->id_menu = (int) $request->id_menu;
            $detail->jumlah = (int) $request->qty;
            $detail->harga_satuan = (float) ($request->harga_satuan ?? $detail->harga_satuan);
            $detail->kustomisasi = $request->input('kustomisasi', $detail->kustomisasi);
            $detail->save();
            $message = 'Item pesanan berhasil diperbarui.';
        } else {
            $detail = $pesanan->detailPesanan()->create([
                'id_menu' => (int) $request->id_menu,
                'jumlah' => (int) $request->qty,
                'harga_satuan' => (float) ($request->harga_satuan ?? 0),
                'kustomisasi' => $request->kustomisasi,
            ]);
            $message = 'Item pesanan berhasil ditambahkan.';
        }

        $pesanan->total_pesanan = $pesanan->detailPesanan()->sum('jumlah');
        $pesanan->total_harga = (float) $pesanan->detailPesanan()->sum(DB::raw('jumlah * harga_satuan'));
        $pesanan->save();

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'id_pesanan' => $pesanan->id_pesanan,
                'id_detail' => $detail->id_detail,
                'item' => $detail,
                'total_pesanan' => $pesanan->total_pesanan,
                'total_harga' => $pesanan->total_harga,
            ],
        ], $detail->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * DELETE /api/customer/orders/{id}/items/{detail_id}
     * Hapus satu item dari detail pesanan.
     */
    public function deleteOrderItem($id, $detail_id)
    {
        $pesanan = Pesanan::with('detailPesanan')->findOrFail($id);
        $detail = $pesanan->detailPesanan()->where('id_detail', $detail_id)->firstOrFail();

        $detail->delete();

        $pesanan->total_pesanan = $pesanan->detailPesanan()->sum('jumlah');
        $pesanan->total_harga = (float) $pesanan->detailPesanan()->sum(DB::raw('jumlah * harga_satuan'));
        $pesanan->save();

        return response()->json([
            'success' => true,
            'message' => 'Item pesanan berhasil dihapus.',
            'data' => [
                'id_pesanan' => $pesanan->id_pesanan,
                'total_pesanan' => $pesanan->total_pesanan,
                'total_harga' => $pesanan->total_harga,
            ],
        ]);
    }

    /**
     * PATCH /api/customer/orders/{id}/items/{detail_id}
     * Update satu item tertentu di dalam pesanan.
     */
    public function updateOrderItem(Request $request, $id, $detail_id)
    {
        $pesanan = Pesanan::with('detailPesanan')->findOrFail($id);
        $detail = $pesanan->detailPesanan()->where('id_detail', $detail_id)->firstOrFail();

        $request->validate([
            'id_menu' => 'sometimes|integer|min:1',
            'qty' => 'sometimes|integer|min:1',
            'harga_satuan' => 'sometimes|numeric|min:0',
            'kustomisasi' => 'sometimes|nullable|string',
        ], [
            'qty.integer' => 'Jumlah item harus berupa angka.',
            'qty.min' => 'Jumlah item minimal 1.',
            'harga_satuan.numeric' => 'Harga satuan harus berupa angka.',
        ]);

        if ($request->has('id_menu')) {
            $detail->id_menu = (int) $request->id_menu;
        }

        if ($request->has('qty')) {
            $detail->jumlah = (int) $request->qty;
        }

        if ($request->has('harga_satuan')) {
            $detail->harga_satuan = (float) $request->harga_satuan;
        }

        if ($request->has('kustomisasi')) {
            $detail->kustomisasi = $request->kustomisasi;
        }

        $detail->save();

        $pesanan->total_pesanan = $pesanan->detailPesanan()->sum('jumlah');
        $pesanan->total_harga = (float) $pesanan->detailPesanan()->sum(DB::raw('jumlah * harga_satuan'));
        $pesanan->save();

        return response()->json([
            'success' => true,
            'message' => 'Detail item pesanan berhasil diperbarui.',
            'data' => [
                'id_pesanan' => $pesanan->id_pesanan,
                'detail' => $detail,
                'total_pesanan' => $pesanan->total_pesanan,
                'total_harga' => $pesanan->total_harga,
            ],
        ]);
    }

    /**
     * GET /api/customer/orders/{id}
     * Cek status pesanan pelanggan.
     */
    public function orderStatus($id)
    {
        $pesanan = Pesanan::with('detailPesanan.menu')->find($id);

        if (! $pesanan) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan.',
            ], 404);
        }

        foreach ($pesanan->detailPesanan as $detail) {
            if ($detail->menu) {
                $detail->menu->makeHidden('foto');
            }
        }

        return response()->json([
            'success' => true,
            'data' => $pesanan,
        ]);
    }

}
