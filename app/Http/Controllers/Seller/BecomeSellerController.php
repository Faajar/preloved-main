<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BecomeSellerController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->role === UserRole::Buyer, 403);

        $request->user()->update(['role' => UserRole::Seller]);

        return redirect()->route('seller.dashboard')->with('status', 'Akun seller aktif. Anda sekarang dapat menitipkan barang.');
    }
}
