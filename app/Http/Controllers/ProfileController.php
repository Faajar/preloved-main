<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ProfileController extends Controller
{
    public function edit(): View
    {
        $user = auth()->user();

        return view('profile.edit', compact('user'));
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->update([
            'name' => $request->validated('name'),
            'whatsapp_number' => $request->validated('whatsapp_number'),
        ]);

        return redirect()->route('profile.edit')->with('status', 'Profil dan kontak WhatsApp berhasil diperbarui.');
    }
}
