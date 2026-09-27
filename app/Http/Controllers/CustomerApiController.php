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
     * POST /api/customer/checkout
     * Membuat pesanan pelanggan dari cart.
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'phone' => ['required', 'regex:/^(\+62|08)[0-9]{8,13}$/'],
            'email' => 'required|email',
            'cart' => 'required|array|min:1',
            'amount' => 'required|numeric|min:1',
        ], [
            'nama.required' => 'Nama pelanggan wajib diisi.',
            'phone.required' => 'Nomor telepon wajib diisi.',
            'phone.regex' => 'Format nomor telepon tidak valid.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'cart.required' => 'Keranjang pesanan tidak boleh kosong.',
            'amount.required' => 'Total pembayaran wajib diisi.',
        ]);

        $merchantCode = env('DUITKU_MERCHANT_CODE');
        $apiKey = env('DUITKU_API_KEY');

        if (! $merchantCode || ! $apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'Konfigurasi Duitku belum diset. Isi DUITKU_MERCHANT_CODE dan DUITKU_API_KEY di file .env.',
            ], 500);
        }

        $cart = $request->input('cart', []);
        $nama = $request->input('nama');
        $phone = $request->input('phone');
        $email = $request->input('email');
        $amount = (float) $request->input('amount');
        $catatan = $request->input('catatan');
        $paymentMethod = $request->input('paymentMethod', 'online');

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

        if (strtolower((string) $paymentMethod) === 'kasir') {
            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil dibuat. Silakan bayar di kasir.',
                'data' => [
                    'id_pesanan' => $pesanan->id_pesanan,
                    'status_pembayaran' => $pesanan->status_pembayaran,
                    'payment_method' => 'kasir',
                ],
            ], 201);
        }

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
                'payment_reference' => $reference,
                'qrString' => $duitkuResponse['qrString'],
                'payment_method' => 'online',
                'status_pembayaran' => $pesanan->status_pembayaran,
                'total_harga' => $pesanan->total_harga,
            ],
        ], 201);
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

    /**
     * GET /api/customer/orders?phone=08xxx
     * Melihat riwayat pesanan berdasarkan nomor telepon pelanggan.
     */
    public function orders(Request $request)
    {
        $request->validate([
            'phone' => ['required_without:email', 'regex:/^(\+62|08)[0-9]{8,13}$/'],
            'email' => 'required_without:phone|email',
        ], [
            'phone.regex' => 'Format nomor telepon tidak valid.',
            'email.email' => 'Format email tidak valid.',
        ]);

        $query = Pesanan::query();

        if ($request->filled('phone')) {
            $query->where('no_telepon', $request->phone);
        }

        if ($request->filled('email')) {
            $query->where('email', $request->email);
        }

        $orders = $query->with('detailPesanan.menu')->orderByDesc('id_pesanan')->get();

        foreach ($orders as $pesanan) {
            foreach ($pesanan->detailPesanan as $detail) {
                if ($detail->menu) {
                    $detail->menu->makeHidden('foto');
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * PATCH /api/customer/orders/{id}
     * Update data pesanan utama dan item detailnya.
     */
    public function updateOrder(Request $request, $id)
    {
        $pesanan = Pesanan::findOrFail($id);

        $request->validate([
            'nama' => 'sometimes|string|max:255',
            'phone' => ['sometimes', 'nullable', 'regex:/^(\+62|08)[0-9]{8,13}$/'],
            'email' => ['sometimes', 'nullable', 'email'],
            'catatan' => 'sometimes|nullable|string',
            'status_pembayaran' => 'sometimes|in:Lunas,Belum Lunas',
            'items' => 'sometimes|array',
            'items.*.id_menu' => 'required_with:items|integer|min:1',
            'items.*.qty' => 'required_with:items|integer|min:1',
            'items.*.harga_satuan' => 'required_with:items|numeric|min:0',
        ], [
            'phone.regex' => 'Format nomor telepon tidak valid.',
            'email.email' => 'Format email tidak valid.',
            'status_pembayaran.in' => 'Status pembayaran harus Lunas atau Belum Lunas.',
            'items.*.id_menu.required_with' => 'ID menu wajib diisi untuk setiap item.',
            'items.*.qty.required_with' => 'Jumlah item wajib diisi.',
            'items.*.harga_satuan.required_with' => 'Harga satuan item wajib diisi.',
        ]);

        $payload = [
            'nama' => $request->input('nama', $pesanan->nama),
            'no_telepon' => $request->input('phone', $pesanan->no_telepon),
            'email' => $request->input('email', $pesanan->email),
            'catatan' => $request->input('catatan', $pesanan->catatan),
        ];

        if ($request->has('status_pembayaran')) {
            $payload['status_pembayaran'] = $request->input('status_pembayaran');
        }

        $pesanan->fill($payload);

        if ($request->has('items')) {
            $items = $request->input('items', []);

            $totalQty = 0;
            $totalHarga = 0;
            $newDetails = [];

            foreach ($items as $item) {
                $qty = (int) ($item['qty'] ?? 0);
                $unitPrice = (float) ($item['harga_satuan'] ?? ($item['price'] ?? 0));
                $totalQty += $qty;
                $totalHarga += $qty * $unitPrice;

                $newDetails[] = [
                    'id_menu' => (int) ($item['id_menu'] ?? $item['id']),
                    'jumlah' => $qty,
                    'harga_satuan' => $unitPrice,
                    'kustomisasi' => $item['kustomisasi'] ?? $item['notes'] ?? $item['note'] ?? null,
                ];
            }

            $pesanan->total_pesanan = $totalQty;
            $pesanan->total_harga = $totalHarga;
            $pesanan->detailPesanan()->delete();

            foreach ($newDetails as $detail) {
                $pesanan->detailPesanan()->create($detail);
            }
        }

        $pesanan->save();

        $pesanan->load('detailPesanan.menu');

        foreach ($pesanan->detailPesanan as $detail) {
            if ($detail->menu) {
                $detail->menu->makeHidden('foto');
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Pesanan berhasil diperbarui.',
            'data' => $pesanan,
        ]);
    }

    /**
     * DELETE /api/customer/orders/{id}
     * Hapus pesanan beserta detail-itemnya.
     */
    public function deleteOrder($id)
    {
        $pesanan = Pesanan::with('detailPesanan')->findOrFail($id);

        $pesanan->detailPesanan()->delete();
        $pesanan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pesanan berhasil dihapus.',
        ]);
    }
}
