<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class ProfilController extends Controller
{
    public function edit(Request $request)
    {
        return view('member.profil', ['member' => $request->user('web')]);
    }

    public function update(Request $request)
    {
        $member = $request->user('web');

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:30',
            'address' => 'nullable|string|max:500',
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ]);

        $member->name = $data['name'];
        $member->phone = $data['phone'];
        $member->address = $data['address'] ?? null;

        if (!empty($data['password'])) {
            $member->password = $data['password'];
        }

        $member->save();

        return back()->with('status', 'Profil berhasil diperbarui.');
    }
}
