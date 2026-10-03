<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Menu;

class MenuController extends Controller
{
    public function index()
    {
        $menus = Menu::where('status_tersedia', true)
            ->get();

        return view('Menu', compact('menus'));
    }

    public function detail($id_menu)
    {
        $menu = Menu::with('bahan')->findOrFail($id_menu);
        return view('Detail_Menu', compact('menu'));
    }

    public function keranjang()
    {
        return view('Keranjang');
    }

    public function pembayaran()
    {
        return view('Pembayaran');
    }
}
