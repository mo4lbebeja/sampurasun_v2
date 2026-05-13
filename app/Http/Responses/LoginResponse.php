<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $request->validate([
            'tahun_anggaran' => ['required', 'integer', 'min:2020', 'max:2100'],
        ]);

        $request->session()->put(
            'tahun_anggaran',
            (int) $request->input('tahun_anggaran')
        );

        return redirect()->intended(config('fortify.home'));
    }
}