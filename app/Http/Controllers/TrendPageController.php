<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TrendPageController extends Controller
{
    public function index()
    {
        $currentUser = Auth::user();

        // Ambil sales list hanya jika superadmin (role_id = 1)
        $salesList = [];
        if (($currentUser->role_id ?? null) == 1) {
            $salesList = User::where('role_id', 12)
                ->select('user_id', 'username')
                ->orderBy('username', 'asc')
                ->get();
        }

        return view('trendbulanan', [
            'currentUser' => $currentUser,
            'salesList' => $salesList,
        ]);
    }
}
