<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Menu;
use App\Models\Pesanan;
use Illuminate\Support\Facades\Response;

class ApiController extends Controller
{
    /**
     * Mengambil daftar kategori dan semua menu untuk aplikasi Android
     */
    public function getMenus()
    {
        $kategoriList = Menu::select('Kategori')
            ->where('status_tersedia', true)
            ->distinct()
            ->pluck('Kategori')
            ->toArray();

        $menus = Menu::where('status_tersedia', true)->get();

        // Menambahkan foto_url agar Android bisa meload gambar
        $menus = $menus->map(function ($menu) {
            $menu->foto_url = url('/api/menu/' . $menu->id_menu . '/foto');
            $menu->makeHidden('foto'); // Sembunyikan field BLOB agar tidak menyebabkan error UTF-8
            return $menu;
        });

        return response()->json([
            'success' => true,
            'data' => [
                'kategoriList' => $kategoriList,
                'menus' => $menus
            ]
        ]);
    }

    /**
     * Mengambil gambar menu (BLOB) dan mengembalikannya sebagai gambar
     */
    public function getMenuPhoto($id)
    {
        $menu = Menu::findOrFail($id);
        
        if ($menu->foto) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->buffer($menu->foto);

            // Default fallback if finfo fails
            if (!$mime) {
                $mime = 'image/jpeg';
            }

            return Response::make($menu->foto, 200, [
                'Content-Type' => $mime,
                'Cache-Control' => 'public, max-age=86400', // Cache gambar selama 1 hari
            ]);
        }
        
        return response()->json(['message' => 'Foto not found'], 404);
    }

    /**
     * Mengambil detail satu menu
     */
    public function getDetailMenu($id)
    {
        $menu = Menu::with('bahan')->findOrFail($id);
        $menu->foto_url = url('/api/menu/' . $menu->id_menu . '/foto');
        $menu->makeHidden('foto');

        return response()->json([
            'success' => true,
            'data' => $menu
        ]);
    }

    /**
     * Mengambil detail pesanan berdasarkan ID
     */
    public function getPesananDetail($id)
    {
        $pesanan = Pesanan::with('detailPesanan.menu')->find($id);

        if ($pesanan) {
            foreach ($pesanan->detailPesanan as $detail) {
                if ($detail->menu) {
                    $detail->menu->makeHidden('foto');
                }
            }

            return response()->json([
                'success' => true,
                'data' => $pesanan
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Pesanan tidak ditemukan'
        ], 404);
    }
}
