<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    /**
     * Tampilkan form login tunggal untuk semua role/aplikasi.
     */
    public function showLogin(): Response|RedirectResponse
    {
        return $this->showLoginForm();
    }

    public function showLoginForm(): Response|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        return Inertia::render('Auth/Login', [
            'status' => session('status'),
        ]);
    }

    /**
     * Proses autentikasi user (bisa menggunakan email atau username).
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $loginType = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $authAttempt = [
            $loginType => $credentials['login'],
            'password' => $credentials['password'],
        ];

        $remember = (bool) ($request->boolean('remember') ?? false);

        if (! Auth::attempt($authAttempt, $remember)) {
            throw ValidationException::withMessages([
                'login' => __('Kredensial yang diberikan tidak cocok dengan data kami.'),
            ]);
        }

        $user = Auth::user();

        // Cek status keaktifan akun jika field is_active bernilai 0/false
        if (isset($user->is_active) && ! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'login' => __('Akun Anda sedang dinonaktifkan. Silakan hubungi Administrator.'),
            ]);
        }

        $request->session()->regenerate();

        return $this->redirectBasedOnRole($user);
    }

    /**
     * Logout pengguna.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil logout.');
    }

    /**
     * Arahkan pengguna ke rute/aplikasi sesuai role yang dimiliki.
     */
    protected function redirectBasedOnRole($user): RedirectResponse
    {
        // Semua user yang berhasil login diarahkan ke Dashboard Terpadu
        // Navigasi & modul di dalamnya otomatis terfilter (hidden) sesuai role
        return redirect()->intended('/dashboard');
    }
}
