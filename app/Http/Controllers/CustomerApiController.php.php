<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\Pesanan;
use Illuminate\Http\Request;

class CustomerApiController extends Controller
{
    public function index()
    {
        $menus = Menu::where('status_tersedia', true)->get()->map(function ($menu) {
            $menu->foto_url = url('/api/menu/' . $menu->id_menu . '/foto');
            $menu->makeHidden('foto');
            return $menu;
        });

        return response()->json([
            'success' => true,
            'data' => $menus
        ]);
    }

    public function show($id)
    {
        $menu = Menu::findOrFail($id);
        $menu->foto_url = url('/api/menu/' . $menu->id_menu . '/foto');
        $menu->makeHidden('foto');

        return response()->json([
            'success' => true,
            'data' => $menu
        ]);
    }

    public function checkout(Request $request)
    {
        // validasi data
        // simpan pesanan
        // return JSON

        return response()->json([
            'success' => true,
            'message' => 'Pesanan berhasil dibuat',
            'data' => [
                'order_id' => 123
            ]
        ]);
    }

    public function orderStatus($id)
    {
        $pesanan = Pesanan::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $pesanan
        ]);
    }
}